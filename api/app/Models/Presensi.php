<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $table = 'presensi';

    protected $fillable = [
        'sesi_id',
        'penempatan_id',
        'tanggal',
        'waktu_masuk',
        'latitude_masuk',
        'longitude_masuk',
        'jarak_masuk_meter',
        'status_masuk',
        'waktu_pulang',
        'latitude_pulang',
        'longitude_pulang',
        'jarak_pulang_meter',
        'status_pulang',
        'status_harian',
    ];
}
