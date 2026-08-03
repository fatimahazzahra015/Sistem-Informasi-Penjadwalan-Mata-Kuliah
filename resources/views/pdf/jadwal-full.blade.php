<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 7px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #000000;
            padding: 6px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0;
            font-size: 12px;
            color: #000000;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 9px; 
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
            padding: 2px 3px;
            text-align: center;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 7px;
        }
        .time-col {
            width: 55px;
            background-color: #f9fafb;
            font-weight: bold;
        }
        .day-col {
            width: 22px;
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .istirahat-row {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
            font-size: 8px;
            letter-spacing: 2px;
            text-align: center;
        }
        .schedule-card {
            border-radius: 2px;
            padding: 2px;
            font-size: 6.5px;
            text-align: left;
            border: 1px solid;
        }
        .course-title {
            font-weight: bold;
            font-size: 7px;
        }
        .details {
            margin-top: 1px;
        }
        tr {
            page-break-inside: avoid;
        }
        thead {
            display: table-header-group;
        }
        .day-block {
            margin-bottom: 15px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Jadwal Perkuliahan Semester {{ $activeSemester->nama }} {{ $activeSemester->tahun_ajaran }}</h2>
        <p>Prodi Teknik Informatika, Universitas Trunojoyo Madura</p>
    </div>

    @php
        $semesterStyles = [
            1 => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#78350f'], // amber
            2 => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e3a8a'], // blue
            3 => ['bg' => '#faf5ff', 'border' => '#e9d5ff', 'text' => '#581c87'], // purple
            4 => ['bg' => '#fef2f2', 'border' => '#fecaca', 'text' => '#7f1d1d'], // red
            5 => ['bg' => '#fdf4ff', 'border' => '#f5d0fe', 'text' => '#701a75'], // fuchsia
            6 => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#064e3b'], // emerald
            7 => ['bg' => '#ecfeff', 'border' => '#a5f3fc', 'text' => '#164e63'], // cyan
            8 => ['bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#7c2d12'], // orange
        ];
        $defaultStyle = ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#0f172a']; // slate

        function styleForSemester($semesterStyles, $defaultStyle, $semester) {
            $key = (int) $semester;
            return $semesterStyles[$key] ?? $defaultStyle;
        }

        $grid = [];
        $covered = [];
        $slotKeys = array_keys($slots);
        sort($slotKeys);

        foreach ($days as $day) {
            $grid[$day] = [];
            $covered[$day] = [];
            foreach ($slotKeys as $slotNum) {
                $grid[$day][$slotNum] = [];
                $covered[$day][$slotNum] = [];
                foreach ($rooms as $room) {
                    $grid[$day][$slotNum][$room->id] = null;
                    $covered[$day][$slotNum][$room->id] = false;
                }
            }
        }

        foreach ($schedules as $sched) {
            $day = $sched->hari;
            $start = (int) $sched->slot_mulai;
            $end = (int) $sched->slot_selesai;
            $roomId = $sched->ruangan_id;

            if (isset($grid[$day][$start])) {
                $grid[$day][$start][$roomId] = $sched;
                for ($s = $start + 1; $s <= $end; $s++) {
                    if (isset($covered[$day][$s])) {
                        $covered[$day][$s][$roomId] = true;
                    }
                }
            }
        }
        $roomCount = count($rooms);

        // Group days into pairs per page: [Senin, Selasa], [Rabu, Kamis], [Jumat]
        $dayChunks = array_chunk($days, 2);
        $lastChunkIndex = count($dayChunks) - 1;
    @endphp

    @foreach($dayChunks as $chunkIndex => $dayChunk)
        <div @if($chunkIndex !== $lastChunkIndex) class="page-break" @endif>
            @foreach($dayChunk as $day)
                <table class="day-block">
                    <thead>
                        <tr>
                            <th style="width: 22px;">Hari</th>
                            <th style="width: 55px;">Waktu</th>
                            @foreach($rooms as $room)
                                <th>{{ $room->nama_ruangan }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php 
                            $daySlots = count($slots);
                            $firstSlot = true;
                        @endphp
                        
                        @foreach($slots as $slotNum => $slotTime)
                            <tr>
                                @if($firstSlot)
                                    <td class="day-col" rowspan="{{ $daySlots }}">{{ $day }}</td>
                                    @php $firstSlot = false; @endphp
                                @endif
                                
                                <td class="time-col">{{ $slotTime }}</td>

                                @if($slotNum == 7)
                                    <td class="istirahat-row" colspan="{{ $roomCount }}">ISTIRAHAT</td>
                                @else
                                    @foreach($rooms as $room)
                                        @if(isset($covered[$day][$slotNum][$room->id]) && $covered[$day][$slotNum][$room->id])
                                            @continue
                                        @endif

                                        @php
                                            $sched = $grid[$day][$slotNum][$room->id] ?? null;
                                        @endphp

                                        @if($sched)
                                            @php
                                                $rowspan = (int)$sched->slot_selesai - (int)$sched->slot_mulai + 1;
                                                $style = styleForSemester($semesterStyles, $defaultStyle, $sched->mataKuliah->semester);
                                            @endphp
                                            <td rowspan="{{ $rowspan }}">
                                                <div class="schedule-card" style="background-color: {{ $style['bg'] }}; border-color: {{ $style['border'] }}; color: {{ $style['text'] }};">
                                                    <div class="course-title" style="color: {{ $style['text'] }};">{{ $sched->mataKuliah->nama }} {{ $sched->kelas->nama_kelas }}</div>
                                                    <div class="details">
                                                        Dosen: {{ $sched->dosen->nama }}
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
            @endforeach
        </div>
    @endforeach
</body>
</html>