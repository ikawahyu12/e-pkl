<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instansi extends Model
{
    protected $table = 'instansi';

    public function picInstansi(): HasMany
    {
        return $this->hasMany(PicInstansi::class, 'instansi_id');
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(PenempatanPkl::class, 'instansi_id');
    }

    public function kuotaPeriode(): HasMany
    {
        return $this->hasMany(KuotaInstansiPeriode::class, 'instansi_id');
    }
}