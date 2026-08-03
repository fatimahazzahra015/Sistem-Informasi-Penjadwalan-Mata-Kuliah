<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $fillable = [
        'semester_id',
        'mata_kuliah_id',
        'kelas_id',
        'dosen_id',
        'ruangan_id',
        'hari',
        'slot_mulai',
        'slot_selesai',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }

    // A helper to get slot time strings
    public static function getSlotTime($slot)
    {
        $slots = [
            1 => '07:00 - 07:50',
            2 => '07:50 - 08:40',
            3 => '08:40 - 09:30',
            4 => '09:30 - 10:20',
            5 => '10:20 - 11:10',
            6 => '11:10 - 12:00',
            7 => '12:00 - 13:00', // ISTIRAHAT
            8 => '13:00 - 13:50',
            9 => '13:50 - 14:40',
            10 => '14:40 - 15:30',
            11 => '15:30 - 16:20',
            12 => '16:20 - 17:10',
            13 => '17:10 - 18:00',
        ];

        return $slots[$slot] ?? '';
    }

    // Helper to get all slot times
    public static function getAllSlots()
    {
        return [
            1 => '07:00 - 07:50',
            2 => '07:50 - 08:40',
            3 => '08:40 - 09:30',
            4 => '09:30 - 10:20',
            5 => '10:20 - 11:10',
            6 => '11:10 - 12:00',
            7 => '12:00 - 13:00', // ISTIRAHAT
            8 => '13:00 - 13:50',
            9 => '13:50 - 14:40',
            10 => '14:40 - 15:30',
            11 => '15:30 - 16:20',
            12 => '16:20 - 17:10',
            13 => '17:10 - 18:00',
        ];
    }
}
