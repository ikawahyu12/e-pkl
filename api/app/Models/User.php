<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'nama_tampilan',
        'foto_profil',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $authPasswordName = 'password_hash';

    protected $rememberTokenName = null;

    protected function casts(): array
    {
        return [
            'wajib_ganti_password' => 'boolean',
            'email_terverifikasi_at' => 'datetime',
            'terakhir_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class, 'user_id');
    }

    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class, 'user_id');
    }
}