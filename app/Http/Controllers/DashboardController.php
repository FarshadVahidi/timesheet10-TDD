<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function authUser()
    {
        $user = Auth::user();
        if($user->hasRole('superadministrator'))
        {
            return view('super/dashboard');
        }else
            return redirect(route('login'));
    }
}
