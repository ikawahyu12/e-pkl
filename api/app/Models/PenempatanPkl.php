<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenempatanPkl extends Model
{
    protected $table = 'penempatan_pkl';

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePkl::class, 'periode_id');
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'instansi_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(PicInstansi::class, 'pic_instansi_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPenempatan::class, 'penempatan_id');
    }

    public function riwayatGuru(): HasMany
    {
        return $this->hasMany(RiwayatPergantianGuru::class, 'penempatan_id');
    }

    public function riwayatPerubahan(): HasMany
    {
        return $this->hasMany(RiwayatPerubahanPenempatan::class, 'penempatan_id');
    }
}