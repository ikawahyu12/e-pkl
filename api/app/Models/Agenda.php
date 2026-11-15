<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agenda extends Model
{
    protected $table = 'agenda_kunjungan';

    protected $fillable = [
        'guru_id',
        'instansi_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'jenis_pencatatan',
        'status',
        'catatan',
        'alasan_pembatalan',
        'dibatalkan_at',
        'dibatalkan_oleh_user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'instansi_id');
    }

    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(
            Siswa::class,
            'agenda_kunjungan_siswa',
            'agenda_kunjungan_id',
            'siswa_id'
        );
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh_user_id');
    }
}
