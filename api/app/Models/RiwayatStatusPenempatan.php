<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusPenempatan extends Model
{
    protected $table = 'riwayat_status_penempatan';

    public $timestamps = false;

    protected $fillable = [
        'penempatan_id',
        'status_lama',
        'status_baru',
        'alasan_kode',
        'alasan_detail',
        'admin_user_id',
        'created_at',
    ];

    protected $casts = [
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