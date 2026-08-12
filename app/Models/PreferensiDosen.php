<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreferensiDosen extends Model
{
    protected $table = 'preferensi_dosen';

    protected $fillable = [
        'dosen_id',
        'kelas_dibuka_id',
        'hari',
        'sesi',
    ];

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function kelasDibuka()
    {
        return $this->belongsTo(KelasDibuka::class, 'kelas_dibuka_id');
    }
}
