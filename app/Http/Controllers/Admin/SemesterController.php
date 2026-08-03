<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SemesterController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Semester/Index', [
            'semestersList' => Semester::orderBy('id', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|in:Ganjil,Genap',
            'tahun_ajaran' => 'required|string|max:50', // e.g. 2023/2024
        ]);

        Semester::create([
            'nama' => $request->nama,
            'tahun_ajaran' => $request->tahun_ajaran,
            'is_active' => false, // new semester is inactive by default
        ]);

        return redirect()->back()->with('success', 'Semester baru berhasil dibuat.');
    }

    public function setActive(Semester $semester)
    {
        DB::transaction(function () use ($semester) {
            // Set all semesters to inactive
            Semester::query()->update(['is_active' => false]);

            // Set target semester to active
            $semester->update(['is_active' => true]);
        });

        return redirect()->back()->with('success', 'Semester aktif berhasil diperbarui.');
    }

    public function viewArchive(Semester $semester)
    {
        $schedules = Jadwal::where('semester_id', $semester->id)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])
            ->get();

        return Inertia::render('Admin/Semester/ArchiveView', [
            'semester' => $semester,
            'schedules' => $schedules,
            'slots' => Jadwal::getAllSlots(),
        ]);
    }

    public function destroy(Semester $semester)
    {
        if ($semester->is_active) {
            return redirect()->back()->with('error', 'Semester aktif tidak dapat dihapus.');
        }

        $semester->delete();
        return redirect()->back()->with('success', 'Semester berhasil dihapus.');
    }
}
