<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanKampus;
use App\Models\KelasDibuka;
use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\Kelas;
use App\Models\Semester;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetupController extends Controller
{
    public function index()
    {
        $dbRooms = Ruangan::all();
        $dbRoomCount = $dbRooms->count();
        $dbClassCount = Kelas::count();
        $activeSemester = Semester::where('is_active', true)->first();

        $pengaturan = PengaturanKampus::firstOrCreate([], [
            'max_kelas_per_semester' => 3,
            'total_ruangan' => $dbRoomCount > 0 ? $dbRoomCount : 15,
            'is_released' => false,
            'is_schedule_published' => false,
        ]);

        // Automatically sync total_ruangan to DB count if available
        if ($dbRoomCount > 0 && $pengaturan->total_ruangan !== $dbRoomCount) {
            $pengaturan->update(['total_ruangan' => $dbRoomCount]);
        }

        // Fetch opened classes specific to active semester
        $kelasDibuka = $activeSemester
            ? KelasDibuka::where('semester_id', $activeSemester->id)->with(['mataKuliah', 'dosen'])->get()
            : [];

        $courses = MataKuliah::all();
        $lecturers = Dosen::all();

        return Inertia::render('Admin/Setup/Index', [
            'pengaturan' => $pengaturan,
            'kelasDibuka' => $kelasDibuka,
            'courses' => $courses,
            'lecturers' => $lecturers,
            'dbRoomCount' => $dbRoomCount,
            'dbRooms' => $dbRooms,
            'dbClassCount' => $dbClassCount,
            'activeSemester' => $activeSemester,
        ]);
    }

    public function updatePengaturan(Request $request)
    {
        $request->validate([
            'max_kelas_per_semester' => 'required|integer|min:1|max:10',
        ]);

        $dbRoomCount = Ruangan::count();
        $pengaturan = PengaturanKampus::firstOrCreate([]);
        $pengaturan->update([
            'max_kelas_per_semester' => $request->max_kelas_per_semester,
            'total_ruangan' => $dbRoomCount > 0 ? $dbRoomCount : 15,
        ]);

        return redirect()->back()->with('success', 'Pengaturan Kampus berhasil diperbarui (Total Ruangan disesuaikan otomatis dengan DB).');
    }

    public function toggleRelease(Request $request)
    {
        $pengaturan = PengaturanKampus::firstOrCreate([]);
        $newStatus = !$pengaturan->is_released;
        $pengaturan->update(['is_released' => $newStatus]);

        $msg = $newStatus
            ? '🚀 Berhasil me-release seluruh alokasi kelas! Dosen sekarang dapat mengisi preferensi mengajar di dashboard.'
            : '🔒 Alokasi kelas ditarik kembali ke mode Draft.';

        return redirect()->back()->with('success', $msg);
    }

    public function storeKelasDibuka(Request $request)
    {
        $activeSemester = Semester::where('is_active', true)->first();
        if (!$activeSemester) {
            return redirect()->back()->withErrors(['semester' => 'Tidak ada semester aktif.']);
        }

        $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliah,id',
            'dosen_ids' => 'required|array|min:1',
            'dosen_ids.*' => 'required|exists:dosen,id',
        ]);

        $dbClassCount = Kelas::count();
        if ($dbClassCount === 0) {
            $dbClassCount = 7; // Default fallback if master kelas table is empty
        }

        $mk = MataKuliah::find($request->mata_kuliah_id);
        $dosenIds = $request->dosen_ids;
        $countToAdd = count($dosenIds);

        $existingCount = KelasDibuka::where('semester_id', $activeSemester->id)
            ->where('mata_kuliah_id', $request->mata_kuliah_id)
            ->count();

        if ($existingCount + $countToAdd > $dbClassCount) {
            return redirect()->back()->withErrors([
                'jumlah_kelas' => "Gagal membuka kelas: Total kelas untuk matkul \"{$mk->nama}\" ({$existingCount} sudah ada + {$countToAdd} baru) melebihi batas maksimal master kelas di database ({$dbClassCount} kelas).",
            ]);
        }

        foreach ($dosenIds as $dosenId) {
            $currentCount = KelasDibuka::where('semester_id', $activeSemester->id)
                ->where('mata_kuliah_id', $request->mata_kuliah_id)
                ->count();

            KelasDibuka::create([
                'semester_id' => $activeSemester->id,
                'mata_kuliah_id' => $request->mata_kuliah_id,
                'dosen_id' => $dosenId,
                'nama_kelas' => 'Slot #' . ($currentCount + 1),
                'status' => 'open',
            ]);
        }

        return redirect()->back()->with('success', "Berhasil membuka {$countToAdd} kelas untuk matkul \"{$mk->nama}\" di semester {$activeSemester->nama} {$activeSemester->tahun_ajaran}.");
    }

    public function destroyKelasDibuka(KelasDibuka $kelasDibuka)
    {
        $kelasDibuka->delete();
        return redirect()->back()->with('success', 'Kelas Dibuka berhasil dihapus.');
    }
}
