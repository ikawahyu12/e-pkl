<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Siswa extends Model
{
    protected $table = 'siswa';

    protected $fillable = [
        'nisn',
        'nis',
        'nama_lengkap',
        'kelas_id',
        'jenis_kelamin',
        'no_hp',
        'alamat',
        'nama_orang_tua',
        'no_hp_orang_tua',
        'status_siswa',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
