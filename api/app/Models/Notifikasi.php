<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    // Nama tabel sesuai database
    protected $table = 'notifikasi';

    // Tabel ini tidak punya kolom updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'tipe',
        'tautan',
        'dibaca_at',
    ];

    protected $casts = [
        'dibaca_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relasi ke tabel users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scope untuk memfilter yang belum dibaca
    public function scopeBelumDibaca($query)
    {
        return $query->whereNull('dibaca_at');
    }
}