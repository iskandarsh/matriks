<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterJabatan extends Model
{
    use SoftDeletes;

    protected $table = 'jabatan';   // ganti kalau nama tabel beda

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_departement',
        'nama'
    ];

    protected $dates = [
        'deleted_at'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'id_departement');
    }
}
