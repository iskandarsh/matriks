<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAbsensi extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'interbat.master_absensi';

    protected $fillable = [
        'mabsKode',
        'mabsKet',
        'maxHari',
        'user_id',
    ];
}
