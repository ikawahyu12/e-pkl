<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPerubahanPenempatan extends Model
{
    protected $table = 'riwayat_perubahan_penempatan';

    public $timestamps = false;

    protected $fillable = [
        'penempatan_id',
        'admin_user_id',
        'perubahan',
        'alasan',
        'created_at',
    ];

    protected $casts = [
        'perubahan' => 'array',
        'created_at' => 'datetime',
    ];

    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanPkl::class, 'penempatan_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
