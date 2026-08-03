<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        /* Pengaturan halaman dinamis berdasarkan mode */
        @page {
            size: A4 {{ $mode === 'timetable' ? 'landscape' : 'portrait' }};
            margin: 12mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: black;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #4b5563;
            font-weight: bold;
        }
        .info {
            margin-bottom: 15px;
        }
        .info table {
            border: none;
            width: auto;
        }
        .info td {
            border: none;
            padding: 2px 8px 2px 0;
            font-size: 11px;
            text-align: left;
        }
        .info td.label {
            font-weight: bold;
            color: black;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #999;
            padding: 6px 5px;
            text-align: center;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background-color: #f3f4f6;
            font-size: 11px;
            color: black;
            font-weight: bold;
        }
        .time-col {
            width: 75px;
            background-color: #f9fafb;
            font-weight: bold;
            color: black;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .istirahat-row {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
            font-size: 11px;
            letter-spacing: 3px;
            text-align: center;
            padding: 8px;
        }
        .schedule-card {
            background-color: #e0f2fe;
            border: 1px solid #bae6fd;
            border-radius: 3px;
            padding: 4px;
            font-size: 9.5px;
            text-align: left;
        }
        .course-title {
            font-weight: bold;
            color: #0369a1;
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        .details {
            color: #4b5563;
            margin-top: 2px;
            line-height: 1.3;
        }
        tr {
            page-break-inside: avoid;
        }
        thead {
            display: table-header-group;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Jadwal Perkuliahan Semester {{ $activeSemester->nama }} {{ $activeSemester->tahun_ajaran }}</h1>
        <p>Prodi Teknik Informatika, Universitas Trunojoyo Madura</p>
    </div>

    <div class="info">
        <table>
            <tr>
                <td class="label">Nama Dosen</td>
                <td>: {{ $dosen->nama }}</td>
            </tr>
        </table>
    </div>

    @php
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $slotKeys = array_keys($slots);
        sort($slotKeys);

        $grid = [];
        $covered = [];
        foreach ($slotKeys as $sNum) {
            foreach ($days as $d) {
                $grid[$sNum][$d] = null;
                $covered[$sNum][$d] = false;
            }
        }

        foreach ($schedules as $sched) {
            $day = trim(ucfirst(strtolower($sched->hari ?? ''))); 
            $start = (int) $sched->slot_mulai;
            $end = (int) $sched->slot_selesai;

            if (isset($grid[$start]) && array_key_exists($day, $grid[$start])) {
                $grid[$start][$day] = $sched;
                for ($s = $start + 1; $s <= $end; $s++) {
                    if (isset($covered[$s][$day])) {
                        $covered[$s][$day] = true;
                    }
                }
            }
        }
        $dayCount = count($days);
    @endphp

    {{-- KONDISI 1: TAMPILAN GRID / TIMETABLE --}}
    @if($mode === 'timetable')
        <table style="table-layout: fixed;">
            <thead>
                <tr>
                    <th>Waktu</th>
                    @foreach($days as $d)
                        <th>{{ $d }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($slotKeys as $slotNum)
                    <tr>
                        <td class="time-col">{{ $slots[$slotNum] }}</td>

                        @if($slotNum == 7)
                            <td class="istirahat-row" colspan="{{ $dayCount }}">ISTIRAHAT</td>
                        @else
                            @foreach($days as $day)
                                @if(isset($covered[$slotNum][$day]) && $covered[$slotNum][$day])
                                    @continue
                                @endif

                                @php
                                    $schedItem = $grid[$slotNum][$day] ?? null;
                                @endphp

                                @if($schedItem)
                                    @php 
                                        $rowspan = (int)$schedItem->slot_selesai - (int)$schedItem->slot_mulai + 1;
                                    @endphp
                                    <td rowspan="{{ $rowspan }}">
                                        <div class="schedule-card">
                                            <div class="course-title">{{ optional($schedItem->mataKuliah)->nama }} {{ optional($schedItem->kelas)->nama_kelas }}</div>
                                            <div class="details">
                                                Ruang: {{ optional($schedItem->ruangan)->nama_ruangan }}<br>
                                                Dosen: {{ optional($schedItem->dosen)->nama }}
                                            </div>
                                        </div>
                                    </td>
                                @else
                                    <td></td>
                                @endif
                            @endforeach
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

    {{-- KONDISI 2: TAMPILAN TABEL / LIST BIASA (PORTRAIT) --}}
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 110px;">Hari</th>
                    <th style="width: 110px;">Waktu</th>
                    <th>Mata Kuliah</th>
                    <th style="width: 40px;">Kelas</th>
                    <th style="width: 90px;">Ruangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules->sortBy(function($item) {
                    $dayWeights = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
                    return ($dayWeights[$item->hari] ?? 9) * 100 + $item->slot_mulai;
                }) as $index => $sched)
                    @php
                        $startSlot = (int)$sched->slot_mulai;
                        $endSlot = (int)$sched->slot_selesai;
                        $timeStartText = explode(' - ', ($slots[$startSlot] ?? '00:00 - 00:00'))[0];
                        $timeEndText = explode(' - ', ($slots[$endSlot] ?? '00:00 - 00:00'))[1] ?? '';
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $sched->hari }}</td>
                        <td>{{ $timeStartText }} - {{ $timeEndText }}</td>
                        <td style="text-align: left; padding-left: 10px;">{{ optional($sched->mataKuliah)->nama ?? '-' }}</td>
                        <td>{{ optional($sched->kelas)->nama_kelas ?? '-' }}</td>
                        <td>{{ optional($sched->ruangan)->nama_ruangan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="color: #64748b; padding: 20px;">Tidak ada jadwal mengajar pada semester aktif.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>