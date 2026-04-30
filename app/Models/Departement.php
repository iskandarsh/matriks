<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departement extends Model
{
    protected $table = 'departments';
    use SoftDeletes;
    protected $fillable = ['depNama', 'depKode', 'mdepKode'];


    public function userDepartemens()
    {
        return $this->hasMany(UserDepartemen::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }
    public function kompetensis()
    {
        return $this->belongsToMany(
            MasterKompetensi::class,
            'kompetensi_depart',
            'depart_id',
            'kompetensi_id'
        );
    }
}
