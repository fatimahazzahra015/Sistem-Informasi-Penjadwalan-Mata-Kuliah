<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Semester;
use App\Models\Ruangan;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Kelas;
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

        if (!$activeSemester) {
            return Inertia::render('Welcome', [
                'schedules' => [],
                'rooms' => [],
                'lecturers' => [],
                'courses' => [],
                'activeSemester' => null,
                'slots' => Jadwal::getAllSlots(),
            ]);
        }

        // Fetch schedules with relationships
        $query = Jadwal::where('semester_id', $activeSemester->id)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan']);

        // Apply filters if any
        if ($request->filled('hari')) {
            $query->where('hari', $request->hari);
        }
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
            'filters' => $request->only(['hari', 'ruangan_id', 'dosen_id', 'mata_kuliah_id']),
        ]);
    }

    /**
     * Display the temporary KRS / personal schedule page for guest students.
     */
    public function guestKrs()
    {
        $activeSemester = Semester::where('is_active', true)->first();

        if (!$activeSemester) {
            return Inertia::render('JadwalSaya', [
                'schedules' => [],
                'activeSemester' => null,
                'slots' => Jadwal::getAllSlots(),
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

        if (!$activeSemester) {
            return Inertia::render('Dosen/Dashboard', [
                'schedules' => [],
                'dosen' => $dosen,
                'activeSemester' => null,
                'slots' => Jadwal::getAllSlots(),
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
        ]);
    }
}
