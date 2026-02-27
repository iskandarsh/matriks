<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKompetensi extends Model
{
    protected $table = 'detail_kompetensi';

    protected $fillable = [
        'id_kompetensi',
        'skala',
        'deskripsi'
    ];

    // Relasi ke master kompetensi
    public function kompetensi()
    {
        return $this->belongsTo(MasterKompetensi::class, 'id_kompetensi');
    }
}
