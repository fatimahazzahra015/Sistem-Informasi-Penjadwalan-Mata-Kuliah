<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelasDibuka extends Model
{
    protected $table = 'kelas_dibuka';

    protected $fillable = [
        'semester_id',
        'mata_kuliah_id',
        'dosen_id',
        'nama_kelas',
        'status',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function preferensi()
    {
        return $this->hasMany(PreferensiDosen::class, 'kelas_dibuka_id');
    }
}
