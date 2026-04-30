<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KompetensiDepart extends Model
{
    protected $table = 'kompetensi_depart';

    protected $fillable = [
        'kompetensi_id',
        'depart_id',
    ];

    public $timestamps = true;

    // Relasi ke kompetensi
    public function kompetensi()
    {
        return $this->belongsTo(MasterKompetensi::class, 'kompetensi_id');
    }

    // Relasi ke depart
    public function depart()
    {
        return $this->belongsTo(Departement::class, 'depart_id');
    }
}
