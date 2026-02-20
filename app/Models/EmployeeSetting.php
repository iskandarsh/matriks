<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSetting extends Model
{
    use SoftDeletes;

    protected $table = 'employee_setting';

    protected $fillable = [
        'id_employee',
        'id_jabatan',
        'type',
        'tahun_berlaku'
    ];

    protected $dates = [
        'deleted_at'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    // employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }

    // jabatan
    public function jabatan()
    {
        return $this->belongsTo(MasterJabatan::class, 'id_jabatan');
    }
}
