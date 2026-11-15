<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPergantianGuru extends Model
{
    protected $table = 'riwayat_pergantian_guru';

    public $timestamps = false;

    protected $fillable = [
        'penempatan_id',
        'guru_lama_id',
        'guru_baru_id',
        'admin_user_id',
        'alasan',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPkl::class, 'penempatan_id');
    }

    public function guruLama(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_lama_id');
    }

    public function guruBaru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_baru_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
