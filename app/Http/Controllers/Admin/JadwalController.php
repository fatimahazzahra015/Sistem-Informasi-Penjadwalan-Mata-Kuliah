<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Semester;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\Kelas;
use App\Models\PengaturanKampus;
use App\Models\PreferensiDosen;
use App\Services\AutoScheduleService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JadwalController extends Controller
{
    public function index()
    {
        $activeSemester = Semester::where('is_active', true)->first();
        $pengaturan = PengaturanKampus::firstOrCreate([], [
            'max_kelas_per_semester' => 3,
            'total_ruangan' => Ruangan::count() > 0 ? Ruangan::count() : 15,
            'is_released' => false,
            'is_schedule_published' => false,
        ]);

        $preferensiList = PreferensiDosen::with(['dosen', 'kelasDibuka.mataKuliah'])
            ->orderBy('created_at', 'asc')
            ->get();

        if (!$activeSemester) {
            return Inertia::render('Admin/Jadwal/Index', [
                'schedules' => [],
                'semesters' => Semester::all(),
                'courses' => [],
                'lecturers' => [],
                'rooms' => [],
                'classes' => [],
                'activeSemester' => null,
                'slots' => Jadwal::getAllSlots(),
                'pengaturan' => $pengaturan,
                'preferensiList' => $preferensiList,
                'isSchedulePublished' => (bool)$pengaturan->is_schedule_published,
            ]);
        }

        $schedules = Jadwal::where('semester_id', $activeSemester->id)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])
            ->get();

        return Inertia::render('Admin/Jadwal/Index', [
            'schedules' => $schedules,
            'semesters' => Semester::all(),
            'courses' => MataKuliah::all(),
            'lecturers' => Dosen::all(),
            'rooms' => Ruangan::all(),
            'classes' => Kelas::all(),
            'activeSemester' => $activeSemester,
            'slots' => Jadwal::getAllSlots(),
            'pengaturan' => $pengaturan,
            'preferensiList' => $preferensiList,
            'isSchedulePublished' => (bool)$pengaturan->is_schedule_published,
        ]);
    }

    /**
     * Publish / Unpublish final schedule to public Welcome.vue & lock lecturer dashboard.
     */
    public function togglePublishSchedule(Request $request)
    {
        $pengaturan = PengaturanKampus::firstOrCreate([]);
        $newStatus = !$pengaturan->is_schedule_published;
        $pengaturan->update(['is_schedule_published' => $newStatus]);

        $msg = $newStatus
            ? '🚀 Jadwal kuliah final BERHASIL dipublikasikan! Jadwal kini tampil di halaman utama (Welcome.vue) dan terkunci di Dashboard Dosen.'
            : '🔒 Publikasi jadwal ditarik kembali ke mode Draft.';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Trigger backend auto schedule engine.
     */
    public function generateAuto(AutoScheduleService $service)
    {
        $result = $service->generate();

        if (!$result['success']) {
            return redirect()->back()->withErrors(['auto' => $result['message']]);
        }

        return redirect()->back()
            ->with('success', $result['message'])
            ->with('generationLogs', $result['logs']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliah,id',
            'kelas_id' => 'required|exists:kelas,id',
            'dosen_id' => 'required|exists:dosen,id',
            'ruangan_id' => 'required|exists:ruangan,id',
            'hari' => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat',
            'slot_mulai' => 'required|integer|min:1|max:13',
            'slot_selesai' => 'required|integer|min:1|max:13|gte:slot_mulai',
        ]);

        $activeSemester = Semester::where('is_active', true)->first();
        if (!$activeSemester) {
            return redirect()->back()->withErrors(['semester' => 'Tidak ada semester aktif.']);
        }

        // Check for conflicts
        $conflict = $this->checkConflict(
            $activeSemester->id,
            $request->hari,
            $request->slot_mulai,
            $request->slot_selesai,
            $request->ruangan_id,
            $request->dosen_id,
            $request->kelas_id,
            $request->mata_kuliah_id
        );

        if ($conflict) {
            return redirect()->back()
                ->withErrors(['conflict' => $conflict['message']])
                ->with('conflictType', $conflict['type'])
                ->withInput();
        }

        Jadwal::create([
            'semester_id' => $activeSemester->id,
            'mata_kuliah_id' => $request->mata_kuliah_id,
            'kelas_id' => $request->kelas_id,
            'dosen_id' => $request->dosen_id,
            'ruangan_id' => $request->ruangan_id,
            'hari' => $request->hari,
            'slot_mulai' => $request->slot_mulai,
            'slot_selesai' => $request->slot_selesai,
        ]);

        return redirect()->back()->with('success', 'Jadwal kuliah berhasil ditambahkan.');
    }

    public function update(Request $request, Jadwal $jadwal)
    {
        $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliah,id',
            'kelas_id' => 'required|exists:kelas,id',
            'dosen_id' => 'required|exists:dosen,id',
            'ruangan_id' => 'required|exists:ruangan,id',
            'hari' => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat',
            'slot_mulai' => 'required|integer|min:1|max:13',
            'slot_selesai' => 'required|integer|min:1|max:13|gte:slot_mulai',
        ]);

        // Check for conflicts excluding the current schedule
        $conflict = $this->checkConflict(
            $jadwal->semester_id,
            $request->hari,
            $request->slot_mulai,
            $request->slot_selesai,
            $request->ruangan_id,
            $request->dosen_id,
            $request->kelas_id,
            $request->mata_kuliah_id,
            $jadwal->id
        );

        if ($conflict) {
            return redirect()->back()
                ->withErrors(['conflict' => $conflict['message']])
                ->with('conflictType', $conflict['type'])
                ->withInput();
        }

        $jadwal->update([
            'mata_kuliah_id' => $request->mata_kuliah_id,
            'kelas_id' => $request->kelas_id,
            'dosen_id' => $request->dosen_id,
            'ruangan_id' => $request->ruangan_id,
            'hari' => $request->hari,
            'slot_mulai' => $request->slot_mulai,
            'slot_selesai' => $request->slot_selesai,
        ]);

        return redirect()->back()->with('success', 'Jadwal kuliah berhasil diperbarui.');
    }

    public function destroy(Jadwal $jadwal)
    {
        $jadwal->delete();
        return redirect()->back()->with('success', 'Jadwal kuliah berhasil dihapus.');
    }

    /**
     * API Endpoint for real-time validation check
     */
    public function checkConflictApi(Request $request)
    {
        $request->validate([
            'hari' => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat',
            'slot_mulai' => 'required|integer|min:1|max:13',
            'slot_selesai' => 'required|integer|min:1|max:13|gte:slot_mulai',
            'ruangan_id' => 'required|integer',
            'dosen_id' => 'required|integer',
            'kelas_id' => 'required|integer',
            'mata_kuliah_id' => 'required|integer',
            'ignore_id' => 'nullable|integer',
        ]);

        $activeSemester = Semester::where('is_active', true)->first();
        if (!$activeSemester) {
            return response()->json([
                'conflict' => false,
                'message' => 'Tidak ada semester aktif.'
            ]);
        }

        $conflict = $this->checkConflict(
            $activeSemester->id,
            $request->hari,
            $request->slot_mulai,
            $request->slot_selesai,
            $request->ruangan_id,
            $request->dosen_id,
            $request->kelas_id,
            $request->mata_kuliah_id,
            $request->ignore_id
        );

        if ($conflict) {
            return response()->json([
                'conflict' => true,
                'type' => $conflict['type'],
                'message' => $conflict['message'],
                'detail' => $conflict['detail']
            ]);
        }

        return response()->json([
            'conflict' => false,
            'message' => 'Tidak ada konflik terdeteksi.'
        ]);
    }

    /**
     * Core conflict checker logic
     */
    private function checkConflict($semester_id, $hari, $slot_mulai, $slot_selesai, $ruangan_id, $dosen_id, $kelas_id, $mata_kuliah_id, $ignore_id = null)
    {
        // 1. Check Room Overlap
        $roomQuery = Jadwal::where('semester_id', $semester_id)
            ->where('hari', $hari)
            ->where('ruangan_id', $ruangan_id)
            ->where(function ($query) use ($slot_mulai, $slot_selesai) {
                $query->where('slot_mulai', '<=', $slot_selesai)
                      ->where('slot_selesai', '>=', $slot_mulai);
            });

        if ($ignore_id) {
            $roomQuery->where('id', '!=', $ignore_id);
        }

        $roomConflict = $roomQuery->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])->first();

        if ($roomConflict) {
            $startStr = explode(' - ', Jadwal::getSlotTime($roomConflict->slot_mulai))[0];
            $endStr = explode(' - ', Jadwal::getSlotTime($roomConflict->slot_selesai))[1];
            return [
                'type' => 'ruangan',
                'message' => "Ruangan {$roomConflict->ruangan->nama_ruangan} telah digunakan oleh mata kuliah \"{$roomConflict->mataKuliah->nama}\" (Kelas {$roomConflict->kelas->nama_kelas}) yang diampu oleh {$roomConflict->dosen->nama} pada hari {$roomConflict->hari} jam {$startStr} - {$endStr}.",
                'detail' => $roomConflict
            ];
        }

        // 2. Check Lecturer Overlap
        $dosenQuery = Jadwal::where('semester_id', $semester_id)
            ->where('hari', $hari)
            ->where('dosen_id', $dosen_id)
            ->where(function ($query) use ($slot_mulai, $slot_selesai) {
                $query->where('slot_mulai', '<=', $slot_selesai)
                      ->where('slot_selesai', '>=', $slot_mulai);
            });

        if ($ignore_id) {
            $dosenQuery->where('id', '!=', $ignore_id);
        }

        $dosenConflict = $dosenQuery->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])->first();

        if ($dosenConflict) {
            $startStr = explode(' - ', Jadwal::getSlotTime($dosenConflict->slot_mulai))[0];
            $endStr = explode(' - ', Jadwal::getSlotTime($dosenConflict->slot_selesai))[1];
            return [
                'type' => 'dosen',
                'message' => "Dosen {$dosenConflict->dosen->nama} sudah dijadwalkan mengajar mata kuliah \"{$dosenConflict->mataKuliah->nama}\" (Kelas {$dosenConflict->kelas->nama_kelas}) di ruangan {$dosenConflict->ruangan->nama_ruangan} pada hari {$dosenConflict->hari} jam {$startStr} - {$endStr}.",
                'detail' => $dosenConflict
            ];
        }

        // 3. Check Kelas
        $kelasQuery = Jadwal::where('semester_id', $semester_id)
            ->where('mata_kuliah_id', $mata_kuliah_id)
            ->where('kelas_id', $kelas_id);

        if ($ignore_id) {
            $kelasQuery->where('id', '!=', $ignore_id);
        }

        $kelasConflict = $kelasQuery->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])->first();

        if ($kelasConflict) {
            $startStr = explode(' - ', Jadwal::getSlotTime($kelasConflict->slot_mulai))[0];
            $endStr = explode(' - ', Jadwal::getSlotTime($kelasConflict->slot_selesai))[1];
            return [
                'type' => 'kelas',
                'message' => "Mata kuliah \"{$kelasConflict->mataKuliah->nama}\" untuk Kelas {$kelasConflict->kelas->nama_kelas} sudah terjadwal pada hari {$kelasConflict->hari} jam {$startStr} - {$endStr} (dengan dosen {$kelasConflict->dosen->nama} di ruangan {$kelasConflict->ruangan->nama_ruangan}). Pilih kelas lain yang belum memiliki jadwal untuk mata kuliah ini.",
                'detail' => $kelasConflict
            ];
        }

        return null;
    }
}