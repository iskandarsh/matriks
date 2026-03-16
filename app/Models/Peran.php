<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Peran extends Model
{
    use SoftDeletes;

    // Nama tabel (opsional, tapi aman ditulis eksplisit)
    protected $table = 'peran';

    // Kolom yang boleh diisi mass assignment
    protected $fillable = [
        'name',
    ];

    // Kalau mau set tipe tanggal (opsional, Laravel biasanya otomatis)
    protected $dates = [
        'deleted_at',
    ];
}
