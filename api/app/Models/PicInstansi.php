<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PicInstansi extends Model
{
    protected $table = 'pic_instansi';

    public $timestamps = false;

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'instansi_id');
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(PenempatanPkl::class, 'pic_instansi_id');
    }
}