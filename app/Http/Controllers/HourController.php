<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HourController extends Controller
{
    public function routeCheck()
    {
        $user = Auth::user();
        if($user->isSuper())
        {
            return view('super.addHour');
        }else
        {
            return redirect(route('login'));
        }

    }
}
