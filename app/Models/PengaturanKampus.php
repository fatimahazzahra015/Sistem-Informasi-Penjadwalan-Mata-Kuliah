<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanKampus extends Model
{
    protected $table = 'pengaturan_kampus';

    protected $fillable = [
        'max_kelas_per_semester',
        'total_ruangan',
        'is_released',
        'is_schedule_published',
    ];

    protected $casts = [
        'is_released' => 'boolean',
        'is_schedule_published' => 'boolean',
    ];
}
