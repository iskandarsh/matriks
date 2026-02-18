<?php

namespace App\Models;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Applicants\Family;
use App\Models\Applicants\Education;
use App\Models\Applicants\WorkExperience;
use App\Models\Applicants\Address;
use App\Models\Applicants\Card;
use App\Models\Departemen;
use App\Models\Employee;
use App\Models\ApplicantStageHistory;
use App\Models\Organization;
use App\Models\PositionSought;
use App\Models\QuestionAnswerUser;

use App\Models\User;

class Applicant extends Model
{
    use HasFactory;
    // use SoftDeletes;
    // Tabel dengan schema eksplisit
    protected $table = 'recruitment.applicants';
    protected $fillable = [
        'appToken',
        'appNama',
        'appTmpLahir',
        'appTglLahir',
        'appSex',
        'appDarah',
        'appAgama',
        'appKK',
        'appNPWP',
        'appHP',
        'appEmail',
        'appStatus',
        'created_at',
        'updated_at',
        'appWN',
        'appPhoto',
        'appStatusapplicant',
        'last_user',
        'id_depar',
        'id_jabatan',
        'mkarKode'
    ];





    public function lastUser()
    {
        return $this->belongsTo(User::class, 'last_user', 'id');
    }


    // App\Models\Applicant.php
    public function employee()
    {
        return $this->hasOne(Employee::class, 'appToken', 'appToken');
    }
}
