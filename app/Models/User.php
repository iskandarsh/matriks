<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Applicant as ModelsApplicant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Applicants\Applicant;
use App\Models\Position;
use App\Models\Departement;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'position_id',
        'start_date',
        'end_date',
        'status_karyawan',
        'empToken',
        'level_user',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions_matriks')
            ->withPivot('menu_id');
    }

    public function permissionsUser()
    {
        return $this->hasMany(UserPermission::class);
    }

    public function permissions_akses()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions_matriks')
            ->withPivot('menu_id')->wherePivot('menu_id', 1);;
    }

    public function applicant()
    {
        return $this->belongsTo(ModelsApplicant::class, 'appToken', 'appToken');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function userDepartemen()
    {
        return $this->hasMany(UserDepartemen::class);
    }

    public function departments()
    {
        return $this->hasManyThrough(
            Departement::class,
            UserDepartemen::class,
            'user_id',       // FK di user_departemen
            'id',            // FK di departments
            'id',            // users.id
            'departemen_id'  // user_departemen.department_id
        );
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'empToken', 'empToken');
    }
}
