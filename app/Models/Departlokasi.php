<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departlokasi extends Model
{
    use HasFactory;

    protected $table = 'departlokasi';

    protected $fillable = [
        'id_lokasi',
        'id_depart',
    ];

    public function department()
    {
        return $this->belongsTo(Departement::class, 'id_depart');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi');
    }
}
