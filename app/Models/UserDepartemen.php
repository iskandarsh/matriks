<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDepartemen extends Model
{
    protected $table = 'user_departemen';

    protected $fillable = [
        'user_id',
        'departemen_id',
        // 'section_id',
        'start_date',
        'end_date',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // (Opsional) Relasi ke Departemen jika kamu punya model Departemen
    public function departemen()
    {
        return $this->belongsTo(Departement::class);
    }

    // (Opsional) Relasi ke Section jika kamu punya model Section
    // public function section()
    // {
    //     return $this->belongsTo(Section::class);
    // }
}
