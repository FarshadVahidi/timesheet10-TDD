<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if($user->isAbleTo('users-create'))
        {
            if(request()->user()->hasRole('superadministrator'))
            {
                return view('super.registration');
            }
        }
    }
}
