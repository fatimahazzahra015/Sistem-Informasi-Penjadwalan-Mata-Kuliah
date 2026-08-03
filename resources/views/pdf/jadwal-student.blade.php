<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
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
            color: #000000;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px; 
            color: #4b5563;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Jadwal Perkuliahan Semester {{ $activeSemester->nama }} {{ $activeSemester->tahun_ajaran }}</h1>
        <p>Prodi Teknik Informatika, Universitas Trunojoyo Madura</p>
    </div>

    @php
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $grid = [];
        $covered = [];
        $slotKeys = array_keys($slots);
        sort($slotKeys);

        foreach ($slotKeys as $slotNum) {
            $grid[$slotNum] = [];
            $covered[$slotNum] = [];
            foreach ($days as $day) {
                $grid[$slotNum][$day] = null;
                $covered[$slotNum][$day] = false;
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

    <table>
        <thead>
            <tr>
                <th style="width: 75px;">Waktu</th>
                @foreach($days as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($slots as $slotNum => $slotTime)
                <tr>
                    <td class="time-col">{{ $slotTime }}</td>

                    {{-- Sesuaikan nomor slot istirahat jika ada, misal slot 7 --}}
                    @if($slotNum == 7)
                        <td class="istirahat-row" colspan="{{ $dayCount }}">ISTIRAHAT</td>
                    @else
                        @foreach($days as $day)
                            {{-- Jika sel tertutup oleh rowspan dari baris atasnya --}}
                            @if(isset($covered[$slotNum][$day]) && $covered[$slotNum][$day])
                                @continue
                            @endif

                            @php
                                $sched = $grid[$slotNum][$day] ?? null;
                            @endphp

                            @if($sched)
                                @php
                                    $rowspan = (int)$sched->slot_selesai - (int)$sched->slot_mulai + 1;
                                @endphp
                                <td rowspan="{{ $rowspan }}">
                                    <div class="schedule-card">
                                        <div class="course-title">{{ optional($sched->mataKuliah)->nama }} {{ optional($sched->kelas)->nama_kelas }}</div>
                                        <div class="details">
                                            Ruang: {{ optional($sched->ruangan)->nama_ruangan }}<br>
                                            Dosen: {{ optional($sched->dosen)->nama }}
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
</body>
</html>