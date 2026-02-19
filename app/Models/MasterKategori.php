<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterKategori extends Model
{
    use SoftDeletes; // untuk mendukung deleted_at

    // Nama tabel jika tidak sesuai pluralisasi Laravel
    protected $table = 'kategori';

    // Primary key (default 'id', bisa dihilangkan jika tetap 'id')
    protected $primaryKey = 'id';

    // Kolom yang bisa diisi massal
    protected $fillable = [
        'nama',
    ];

    // Timestamps otomatis (created_at & updated_at)
    public $timestamps = true;

    // Kolom soft delete
    protected $dates = ['deleted_at'];
}
