<?php

namespace App\Http\Controllers;

use App\Models\CreateUser;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Helpers\EncryptHelper;
use App\Models\Applicants\Applicant;
use App\Models\Departement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUserController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {

        return view('user_management.create_user');
    }
}
