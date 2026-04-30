<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterKompetensi extends Model
{
    use SoftDeletes;

    protected $table = 'kompetensi';

    protected $fillable = [
        'nama',
        'initial',
        'deskripsi',
        'kategori_id' // 🔥 tambahin ini
    ];

    public function details()
    {
        return $this->hasMany(DetailKompetensi::class, 'id_kompetensi');
    }

    // Relasi ke kategori
    public function kategori()
    {
        return $this->belongsTo(MasterKategori::class, 'kategori_id');
    }
    public function departs()
    {
        return $this->belongsToMany(
            Departement::class,
            'kompetensi_depart',
            'kompetensi_id',
            'depart_id'
        );
    }
}
