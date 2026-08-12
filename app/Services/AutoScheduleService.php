<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Semester;
use App\Models\PengaturanKampus;
use App\Models\PreferensiDosen;
use App\Models\KelasDibuka;
use App\Models\Ruangan;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;

class AutoScheduleService
{
    public function generate()
    {
        $activeSemester = Semester::where('is_active', true)->first();
        if (!$activeSemester) {
            return [
                'success' => false,
                'message' => 'Tidak ada semester aktif saat ini.',
                'logs' => [],
            ];
        }

        $pengaturan = PengaturanKampus::first();
        $totalRuangan = $pengaturan ? $pengaturan->total_ruangan : Ruangan::count();
        $maxKelasPerSemester = $pengaturan ? $pengaturan->max_kelas_per_semester : 3;

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $dayWeights = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
        $rooms = Ruangan::all();

        if ($rooms->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Belum ada data ruangan di sistem.',
                'logs' => [],
            ];
        }

        // Fetch preferences specific to active semester ordered by timestamp ASC (First Submit, First Served)
        $preferences = PreferensiDosen::whereHas('kelasDibuka', function ($q) use ($activeSemester) {
                $q->where('semester_id', $activeSemester->id);
            })
            ->with(['dosen', 'kelasDibuka.mataKuliah'])
            ->orderBy('created_at', 'asc')
            ->get();

        // If no preference submitted yet, fallback to opened classes for active semester
        if ($preferences->isEmpty()) {
            $openedClasses = KelasDibuka::where('semester_id', $activeSemester->id)
                ->with(['dosen', 'mataKuliah'])
                ->get();

            if ($openedClasses->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Belum ada data Kelas Dibuka atau Preferensi Dosen yang diinput untuk semester aktif ini.',
                    'logs' => [],
                ];
            }
        }

        // Reset current schedules for active semester
        Jadwal::where('semester_id', $activeSemester->id)->delete();

        $logs = [];
        $scheduledItems = []; // Array of allocated schedules
        $createdJadwalModels = [];

        $itemsToProcess = [];

        if ($preferences->isNotEmpty()) {
            foreach ($preferences as $pref) {
                $itemsToProcess[] = [
                    'dosen_id' => $pref->dosen_id,
                    'dosen_nama' => $pref->dosen->nama ?? 'Dosen',
                    'kelas_dibuka' => $pref->kelasDibuka,
                    'mata_kuliah' => $pref->kelasDibuka->mataKuliah,
                    'requested_hari' => $pref->hari,
                    'requested_sesi' => $pref->sesi,
                    'timestamp' => $pref->created_at->format('H:i:s d/m/Y'),
                ];
            }
        } else {
            foreach ($openedClasses as $opened) {
                if ($opened->dosen_id) {
                    $itemsToProcess[] = [
                        'dosen_id' => $opened->dosen_id,
                        'dosen_nama' => $opened->dosen->nama ?? 'Dosen',
                        'kelas_dibuka' => $opened,
                        'mata_kuliah' => $opened->mataKuliah,
                        'requested_hari' => 'Senin',
                        'requested_sesi' => 1,
                        'timestamp' => 'Default Admin',
                    ];
                }
            }
        }

        // Dummy initial kelas for creation, will be re-assigned chronologically A, B, C...
        $defaultKelas = Kelas::firstOrCreate(['nama_kelas' => 'A']);

        // Process each item
        foreach ($itemsToProcess as $item) {
            $dosenId = $item['dosen_id'];
            $dosenNama = $item['dosen_nama'];
            $mk = $item['mata_kuliah'];
            $requestedHari = $item['requested_hari'];
            $requestedSesi = $item['requested_sesi'];
            $semesterMatkul = $mk->semester;
            $sks = $mk->sks ?? 3;
            $duration = min($sks, 3); // Max 3 slots per class session

            $allocated = false;
            $shiftReason = '';

            // Day search order starting from requestedHari
            $dayIndex = array_search($requestedHari, $days);
            if ($dayIndex === false) $dayIndex = 0;

            $orderedDays = array_merge(
                array_slice($days, $dayIndex),
                array_slice($days, 0, $dayIndex)
            );

            foreach ($orderedDays as $currentDay) {
                $startSesiRange = ($currentDay === $requestedHari) ? $requestedSesi : 1;

                for ($startSlot = $startSesiRange; $startSlot <= (13 - $duration + 1); $startSlot++) {
                    $endSlot = $startSlot + $duration - 1;

                    // Skip slot 7 (12:00 - 13:00 Istirahat) if possible
                    if ($startSlot <= 7 && $endSlot >= 7) {
                        continue;
                    }

                    // Check Filter 1: Ketersediaan Total Ruangan
                    $filter1Passed = true;
                    for ($s = $startSlot; $s <= $endSlot; $s++) {
                        $countAllClasses = 0;
                        foreach ($scheduledItems as $sch) {
                            if ($sch['hari'] === $currentDay && $sch['slot_mulai'] <= $s && $sch['slot_selesai'] >= $s) {
                                $countAllClasses++;
                            }
                        }
                        if ($countAllClasses >= $totalRuangan) {
                            $filter1Passed = false;
                            $shiftReason = "Total ruangan penuh ({$totalRuangan}/{$totalRuangan}) pada {$currentDay} Sesi {$s}";
                            break;
                        }
                    }
                    if (!$filter1Passed) continue;

                    // Check Filter 2: Kuota Max Kelas Per Semester
                    $filter2Passed = true;
                    for ($s = $startSlot; $s <= $endSlot; $s++) {
                        $countSemesterClasses = 0;
                        foreach ($scheduledItems as $sch) {
                            if ($sch['hari'] === $currentDay && $sch['semester'] == $semesterMatkul && $sch['slot_mulai'] <= $s && $sch['slot_selesai'] >= $s) {
                                $countSemesterClasses++;
                            }
                        }
                        if ($countSemesterClasses >= $maxKelasPerSemester) {
                            $filter2Passed = false;
                            $shiftReason = "Kuota max kelas Semester {$semesterMatkul} sudah penuh ({$maxKelasPerSemester}/{$maxKelasPerSemester}) pada {$currentDay} Sesi {$s}";
                            break;
                        }
                    }
                    if (!$filter2Passed) continue;

                    // Check Dosen Conflict
                    $dosenFree = true;
                    foreach ($scheduledItems as $sch) {
                        if ($sch['hari'] === $currentDay && $sch['dosen_id'] == $dosenId) {
                            if ($startSlot <= $sch['slot_selesai'] && $endSlot >= $sch['slot_mulai']) {
                                $dosenFree = false;
                                $shiftReason = "Dosen {$dosenNama} sudah mengajar kelas lain di {$currentDay} Sesi {$startSlot}";
                                break;
                            }
                        }
                    }
                    if (!$dosenFree) continue;

                    // Find Available Room
                    $availableRoom = null;
                    foreach ($rooms as $room) {
                        $roomFree = true;
                        foreach ($scheduledItems as $sch) {
                            if ($sch['hari'] === $currentDay && $sch['ruangan_id'] == $room->id) {
                                if ($startSlot <= $sch['slot_selesai'] && $endSlot >= $sch['slot_mulai']) {
                                    $roomFree = false;
                                    break;
                                }
                            }
                        }
                        if ($roomFree) {
                            $availableRoom = $room;
                            break;
                        }
                    }

                    if (!$availableRoom) {
                        $shiftReason = "Tidak ada fisik ruangan kosong pada {$currentDay} Sesi {$startSlot}";
                        continue;
                    }

                    // Success! Allocate schedule
                    $j = Jadwal::create([
                        'semester_id' => $activeSemester->id,
                        'mata_kuliah_id' => $mk->id,
                        'kelas_id' => $defaultKelas->id,
                        'dosen_id' => $dosenId,
                        'ruangan_id' => $availableRoom->id,
                        'hari' => $currentDay,
                        'slot_mulai' => $startSlot,
                        'slot_selesai' => $endSlot,
                    ]);

                    $createdJadwalModels[] = [
                        'jadwal' => $j,
                        'mk_id' => $mk->id,
                        'dosen_nama' => $dosenNama,
                        'mk_nama' => $mk->nama,
                        'semester' => $semesterMatkul,
                        'hari' => $currentDay,
                        'slot_mulai' => $startSlot,
                        'slot_selesai' => $endSlot,
                        'ruangan_nama' => $availableRoom->nama_ruangan,
                        'is_preferred' => ($currentDay === $requestedHari && $startSlot == $requestedSesi),
                        'shift_reason' => $shiftReason,
                        'requested_hari' => $requestedHari,
                        'requested_sesi' => $requestedSesi,
                    ];

                    $scheduledItems[] = [
                        'semester' => $semesterMatkul,
                        'hari' => $currentDay,
                        'slot_mulai' => $startSlot,
                        'slot_selesai' => $endSlot,
                        'dosen_id' => $dosenId,
                        'ruangan_id' => $availableRoom->id,
                    ];

                    $allocated = true;
                    break 2;
                }
            }

            if (!$allocated) {
                $logs[] = [
                    'type' => 'failed',
                    'message' => "✗ {$dosenNama} - {$mk->nama} gagal dijadwalkan karena semua slot penuh.",
                ];
            }
        }

        // --- AUTOMATIC CLASS NAMING (KELAS A, B, C, D...) BASED ON CHRONOLOGICAL ORDER ---
        // Group created schedules by mata_kuliah_id
        $groupedByCourse = [];
        foreach ($createdJadwalModels as $item) {
            $groupedByCourse[$item['mk_id']][] = $item;
        }

        $alphabet = range('A', 'Z');

        foreach ($groupedByCourse as $mkId => $items) {
            // Sort chronologically: Hari (1..5) then slot_mulai (1..13)
            usort($items, function ($a, $b) use ($dayWeights) {
                $wA = $dayWeights[$a['hari']] ?? 9;
                $wB = $dayWeights[$b['hari']] ?? 9;
                if ($wA !== $wB) return $wA - $wB;
                return $a['slot_mulai'] - $b['slot_mulai'];
            });

            // Assign Kelas A, B, C... according to chronological order
            foreach ($items as $idx => $item) {
                $letter = $alphabet[$idx] ?? ('A' . ($idx + 1));
                $kelasModel = Kelas::firstOrCreate(['nama_kelas' => $letter]);

                // Update database record
                $item['jadwal']->update(['kelas_id' => $kelasModel->id]);

                // Format log message with the newly assigned class name (Kelas A, B, C...)
                if ($item['is_preferred']) {
                    $logs[] = [
                        'type' => 'success',
                        'message' => "✓ {$item['dosen_nama']} - {$item['mk_nama']} (Kelas {$letter}) [Sem {$item['semester']}] berhasil ditempatkan di {$item['hari']} Sesi {$item['slot_mulai']} - {$item['slot_selesai']} di Ruang {$item['ruangan_nama']} (Sesi Pilihan Utama).",
                    ];
                } else {
                    $logs[] = [
                        'type' => 'shifted',
                        'message' => "⚡ {$item['dosen_nama']} - {$item['mk_nama']} (Kelas {$letter}) [Sem {$item['semester']}] digeser dari request ({$item['requested_hari']} Sesi {$item['requested_sesi']}) ke {$item['hari']} Sesi {$item['slot_mulai']} - {$item['slot_selesai']} di Ruang {$item['ruangan_nama']} (Alasan: {$item['shift_reason']}).",
                    ];
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Penjadwalan otomatis selesai diproses. Nama Kelas (Kelas A, B, C...) berhasil diurutkan otomatis berdasar jadwal paling awal.',
            'logs' => $logs,
            'count' => count($scheduledItems),
        ];
    }
}
