<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuotaInstansiPeriode extends Model
{
    protected $table = 'kuota_instansi_periode';

    protected $fillable = [
        'instansi_id',
        'periode_id',
        'kuota_siswa',
    ];

    protected $casts = [
        'kuota_siswa' => 'integer',
    ];

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'instansi_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePkl::class, 'periode_id');
    }
}