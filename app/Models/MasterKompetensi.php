<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterKompetensi extends Model
{
    use SoftDeletes;

    protected $table = 'kompetensi';

    protected $fillable = ['nama', 'initial', 'deskripsi'];

    public function details()
    {
        return $this->hasMany(DetailKompetensi::class, 'id_kompetensi');
    }
}
