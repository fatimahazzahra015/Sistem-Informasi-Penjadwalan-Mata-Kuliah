<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Semester;
use App\Models\Ruangan;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Kelas;
use App\Models\KelasDibuka;
use App\Models\PreferensiDosen;
use App\Models\PengaturanKampus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ScheduleViewController extends Controller
{
    /**
     * Display the public full schedule grid page.
     */
    public function index(Request $request)
    {
        $activeSemester = Semester::where('is_active', true)->first();
        $pengaturan = PengaturanKampus::first();
        $isPublished = $pengaturan ? (bool)$pengaturan->is_schedule_published : false;

        if (!$activeSemester || !$isPublished) {
            return Inertia::render('Welcome', [
                'schedules' => [],
                'rooms' => Ruangan::all(),
                'lecturers' => Dosen::all(),
                'courses' => MataKuliah::all(),
                'activeSemester' => $activeSemester,
                'slots' => Jadwal::getAllSlots(),
                'isSchedulePublished' => false,
            ]);
        }

        // Fetch schedules with relationships
        $query = Jadwal::where('semester_id', $activeSemester->id)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan']);

        // Default filter hari to 'Senin' if not specified
        $selectedHari = $request->input('hari', 'Senin');
        $query->where('hari', $selectedHari);

        if ($request->filled('ruangan_id')) {
            $query->where('ruangan_id', $request->ruangan_id);
        }
        if ($request->filled('dosen_id')) {
            $query->where('dosen_id', $request->dosen_id);
        }
        if ($request->filled('mata_kuliah_id')) {
            $query->where('mata_kuliah_id', $request->mata_kuliah_id);
        }

        $schedules = $query->get();

        return Inertia::render('Welcome', [
            'schedules' => $schedules,
            'rooms' => Ruangan::all(),
            'lecturers' => Dosen::all(),
            'courses' => MataKuliah::all(),
            'activeSemester' => $activeSemester,
            'slots' => Jadwal::getAllSlots(),
            'filters' => [
                'hari' => $selectedHari,
                'ruangan_id' => $request->ruangan_id,
                'dosen_id' => $request->dosen_id,
                'mata_kuliah_id' => $request->mata_kuliah_id,
            ],
            'isSchedulePublished' => true,
        ]);
    }

    /**
     * Display the temporary KRS / personal schedule page for guest students.
     */
    public function guestKrs()
    {
        $activeSemester = Semester::where('is_active', true)->first();
        $pengaturan = PengaturanKampus::first();
        $isPublished = $pengaturan ? (bool)$pengaturan->is_schedule_published : false;

        if (!$activeSemester || !$isPublished) {
            return Inertia::render('JadwalSaya', [
                'schedules' => [],
                'allSchedules' => [],
                'activeSemester' => $activeSemester,
                'slots' => Jadwal::getAllSlots(),
                'isSchedulePublished' => false,
            ]);
        }

        // Fetch all schedules for selection on client-side
        $allSchedules = Jadwal::where('semester_id', $activeSemester->id)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])
            ->get();

        return Inertia::render('JadwalSaya', [
            'allSchedules' => $allSchedules,
            'activeSemester' => $activeSemester,
            'slots' => Jadwal::getAllSlots(),
            'isSchedulePublished' => true,
        ]);
    }

    /**
     * Redirect to role-based dashboard after login.
     */
    public function dashboardRedirect()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.jadwal');
        } elseif ($user->isDosen()) {
            return redirect()->route('dosen.dashboard');
        }

        return redirect('/');
    }

    /**
     * Display the lecturer personal schedule dashboard.
     */
    public function dosenDashboard(Request $request)
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        if (!$dosen) {
            abort(403, 'Akses ditolak: Anda tidak terdaftar sebagai dosen.');
        }

        $activeSemester = Semester::where('is_active', true)->first();

        $pengaturan = PengaturanKampus::first();
        $isReleased = $pengaturan ? (bool)$pengaturan->is_released : false;
        $isSchedulePublished = $pengaturan ? (bool)$pengaturan->is_schedule_published : false;

        // Opened classes allocated to this lecturer for active semester
        $allocatedClasses = $activeSemester
            ? KelasDibuka::where('semester_id', $activeSemester->id)
                ->where('dosen_id', $dosen->id)
                ->with('mataKuliah')
                ->get()
            : [];

        // Preferences submitted by this lecturer for active semester
        $preferensi = $activeSemester
            ? PreferensiDosen::where('dosen_id', $dosen->id)
                ->whereHas('kelasDibuka', function ($q) use ($activeSemester) {
                    $q->where('semester_id', $activeSemester->id);
                })
                ->with('kelasDibuka.mataKuliah')
                ->orderBy('created_at', 'desc')
                ->get()
            : [];

        if (!$activeSemester) {
            return Inertia::render('Dosen/Dashboard', [
                'schedules' => [],
                'dosen' => $dosen,
                'activeSemester' => null,
                'slots' => Jadwal::getAllSlots(),
                'allocatedClasses' => $allocatedClasses,
                'preferensi' => $preferensi,
                'isReleased' => $isReleased,
                'isSchedulePublished' => $isSchedulePublished,
            ]);
        }

        $schedules = Jadwal::where('semester_id', $activeSemester->id)
            ->where('dosen_id', $dosen->id)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])
            ->get();

        return Inertia::render('Dosen/Dashboard', [
            'schedules' => $schedules,
            'dosen' => $dosen,
            'activeSemester' => $activeSemester,
            'slots' => Jadwal::getAllSlots(),
            'allocatedClasses' => $allocatedClasses,
            'preferensi' => $preferensi,
            'isReleased' => $isReleased,
            'isSchedulePublished' => $isSchedulePublished,
        ]);
    }

    /**
     * Dosen submits preference: Class -> Day -> Sesi (Initial Slot).
     */
    public function storePreferensiDosen(Request $request)
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        if (!$dosen) {
            return redirect()->back()->withErrors(['dosen' => 'Anda bukan dosen.']);
        }

        $pengaturan = PengaturanKampus::first();
        if ($pengaturan && $pengaturan->is_schedule_published) {
            return redirect()->back()->withErrors(['published' => 'Jadwal final telah dipublikasikan dan terkunci. Pengubahan preferensi sudah ditutup.']);
        }

        $request->validate([
            'kelas_dibuka_id' => 'required|exists:kelas_dibuka,id',
            'hari' => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat',
            'sesi' => 'required|integer|min:1|max:13',
        ]);

        // Upsert preference for this lecturer & class
        PreferensiDosen::updateOrCreate(
            [
                'dosen_id' => $dosen->id,
                'kelas_dibuka_id' => $request->kelas_dibuka_id,
            ],
            [
                'hari' => $request->hari,
                'sesi' => $request->sesi,
            ]
        );

        return redirect()->back()->with('success', 'Preferensi jadwal berhasil disimpan! Waktu submit telah dicatat sebagai prioritas.');
    }

    /**
     * Dosen deletes preference.
     */
    public function destroyPreferensiDosen(PreferensiDosen $preferensi)
    {
        $user = Auth::user();
        if ($user->dosen && $preferensi->dosen_id === $user->dosen->id) {
            $pengaturan = PengaturanKampus::first();
            if ($pengaturan && $pengaturan->is_schedule_published) {
                return redirect()->back()->withErrors(['published' => 'Jadwal final telah dipublikasikan dan terkunci.']);
            }
            $preferensi->delete();
            return redirect()->back()->with('success', 'Preferensi berhasil dihapus.');
        }

        return redirect()->back()->withErrors(['auth' => 'Aksi tidak diizinkan.']);
    }
}
