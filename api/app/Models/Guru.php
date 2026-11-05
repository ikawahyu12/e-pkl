<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guru extends Model
{
    protected $table = 'guru';

    protected $fillable = [
        'nip',
        'nama_lengkap',
        'jenis_kelamin',
        'no_hp',
        'alamat',
        'mata_pelajaran',
        'jurusan_id',
        'status_bimbingan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
