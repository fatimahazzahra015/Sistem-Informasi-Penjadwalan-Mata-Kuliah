<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Semester;
use App\Models\Dosen;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PDFExportController extends Controller
{
    /**
     * Export the full schedule of the active semester.
     */
    public function exportFull()
    {
        $activeSemester = Semester::where('is_active', true)->first();

        if (!$activeSemester) {
            abort(404, 'Tidak ada semester aktif.');
        }

        $schedules = Jadwal::where('semester_id', $activeSemester->id)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])
            ->get();

        $rooms = $schedules->pluck('ruangan')->unique('id')->sortBy('nama_ruangan');
        $slots = Jadwal::getAllSlots();
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $pdf = Pdf::loadView('pdf.jadwal-full', [
            'schedules' => $schedules,
            'activeSemester' => $activeSemester,
            'rooms' => $rooms,
            'slots' => $slots,
            'days' => $days,
            'title' => 'Jadwal Perkuliahan Keseluruhan'
        ])->setPaper('a4', 'landscape'); // Grid schedule looks best in landscape

        return $pdf->stream('jadwal-kuliah-keseluruhan.pdf');
    }

/**
     * Export personal schedule for a logged-in lecturer.
     */
    public function exportDosen(Request $request)
    {
        $user = Auth::user();
        $dosen = $user->dosen;

        if (!$dosen) {
            abort(403, 'Akses ditolak: Anda tidak terdaftar sebagai dosen.');
        }

        $activeSemester = Semester::where('is_active', true)->first();

        if (!$activeSemester) {
            abort(404, 'Tidak ada semester aktif.');
        }

        $schedules = Jadwal::where('semester_id', $activeSemester->id)
            ->where('dosen_id', $dosen->id)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])
            ->get();

        $mode = $request->input('mode', 'timetable');

        // Definisi slot waktu standar
        $slots = [
            1 => '07:00 - 07:50',
            2 => '07:50 - 08:40',
            3 => '08:40 - 09:30',
            4 => '09:30 - 10:20',
            5 => '10:20 - 11:10',
            6 => '11:10 - 12:00',
            7 => '12:00 - 13:00', // Istirahat / Sholat
            8 => '13:00 - 13:50',
            9 => '13:50 - 14:40',
            10 => '14:40 - 15:30',
            11 => '15:30 - 16:20',
            12 => '16:20 - 17:10',
            13 => '17:10 - 18:00',
        ];

        // Normalisasi data jadwal untuk memastikan format hari konsisten (Title Case)
        $schedules->each(function($item) {
            $normalizedDay = ucfirst(strtolower(trim($item->hari ?? '')));
            // Tangani pemetaan jika di database menggunakan bahasa Inggris atau format lain
            $mapDays = [
                'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 
                'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
            ];
            $item->normalized_hari = $mapDays[$normalizedDay] ?? $normalizedDay;
        });

        $pdf = Pdf::loadView('pdf.jadwal-dosen', [
            'schedules' => $schedules,
            'activeSemester' => $activeSemester,
            'dosen' => $dosen,
            'slots' => $slots,
            'mode' => $mode,
            'title' => 'Jadwal Mengajar Dosen'
        ]);

        if ($mode === 'timetable') {
            $pdf->setPaper('a4', 'landscape');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        return $pdf->stream('jadwal-mengajar-' . strtolower(str_replace(' ', '-', $dosen->nama)) . '.pdf');
    }

    /**
     * Export personal schedule for guest students based on selected temporary KRS IDs.
     */
    public function exportStudent(Request $request)
    {
        $idsStr = $request->query('ids', '');
        $ids = array_filter(explode(',', $idsStr));

        if (empty($ids)) {
            abort(400, 'Tidak ada mata kuliah yang dipilih.');
        }

        $activeSemester = Semester::where('is_active', true)->first();

        if (!$activeSemester) {
            abort(404, 'Tidak ada semester aktif.');
        }

        $schedules = Jadwal::whereIn('id', $ids)
            ->with(['mataKuliah', 'kelas', 'dosen', 'ruangan'])
            ->get()
            ->sortBy(function($item) {
                $dayWeights = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
                return ($dayWeights[$item->hari] ?? 9) * 100 + $item->slot_mulai;
            });

        // Tambahkan baris ini untuk mendefinisikan slots
        $slots = Jadwal::getAllSlots();

        $pdf = Pdf::loadView('pdf.jadwal-student', [
            'schedules' => $schedules,
            'activeSemester' => $activeSemester,
            'slots' => $slots, // Masukkan ke dalam array data view PDF
            'title' => 'Jadwal Kuliah Mahasiswa'
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('jadwal-kuliah-mahasiswa.pdf');
    }
}
