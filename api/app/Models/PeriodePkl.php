<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodePkl extends Model
{
    protected $table = 'periode_pkl';

    public $timestamps = false;

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(PenempatanPkl::class, 'periode_id');
    }

    public function kuotaInstansi(): HasMany
    {
        return $this->hasMany(KuotaInstansiPeriode::class, 'periode_id');
    }
}