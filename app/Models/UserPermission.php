<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = 'user_permissions_matriks'; // Gunakan jamak sesuai standar Laravel

    protected $fillable = [
        'user_id',
        'menu_id',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke menu
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
