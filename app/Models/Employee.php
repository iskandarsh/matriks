<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Employee extends Model
{
    use SoftDeletes;
    protected $table = 'personalia.employees';
    protected $fillable = [
        'empToken',
        'appToken',
        'empRegister',
        'empDatein',
        'empDateout',
        'empDatetrial',
        'department_id',
        'workunit_id',
        'position_id',
        'source_id',
        'bank_id',
        'taxKode',
        'transKode',
        'bpjsKode',
        'empStatus',
        'empKategori',
        'empGol',
        'empTHR',
        'empPoint',
        'empBankcab',
        'empBankacc',
        'empBanknama',
        'empAstekno',
        'empAstekdate',
        'empAstekpensiun',
        'empBpjsno',
        'empBpjsdate',
        'empBpjspeserta',
        'empFaskesnama',
        'empFaskeskode',
        'empKelasrawat',
        'empSetting',
        'empOldkode',
        'empHrdcode',
        'level_gaji',
        'parent_id',
        'id_kompetensi_jabatan',
    ];

    protected $dates = [
        'empDatein',
        'empDateout',
        'empDatetrial',
        'empAstekdate',
        'empBpjsdate',
    ];

    // Relasi ke Applicant
    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'appToken', 'appToken');
    }

    public function department()
    {
        return $this->belongsTo(Departement::class);
    }

    public function workunit()
    {
        return $this->belongsTo(Workunit::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }



    // Atasan langsung (parent)
    public function parent()
    {
        return $this->belongsTo(Employee::class, 'parent_id');
    }

    // Bawahan langsung (children)
    public function children()
    {
        return $this->hasMany(Employee::class, 'parent_id');
    }
}
