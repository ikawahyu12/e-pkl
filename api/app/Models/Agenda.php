<?php

// Simpan di: app/Models/Agenda.php

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
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];

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
}