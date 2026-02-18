<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gedung extends Model
{
    use SoftDeletes;

    protected $fillable = ['geduKode', 'geduNama'];

    public function lokasis()
    {
        return $this->hasMany(Lokasi::class, 'gedung_id');
    }
}
