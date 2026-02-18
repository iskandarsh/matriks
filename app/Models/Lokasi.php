<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lokasi extends Model
{
    use SoftDeletes;

    protected $table = 'lokasis';

    protected $fillable = [
        'lokaNomor',
        'lokaNama',
        'lokaLevel',
        'gedung_id',
    ];

    public function gedung()
    {
        return $this->belongsTo(Gedung::class, 'gedung_id');
    }
    public function departlokasis()
    {
        return $this->hasMany(Departlokasi::class, 'id_lokasi');
    }
}
