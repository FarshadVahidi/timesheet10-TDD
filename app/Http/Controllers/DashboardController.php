<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardCountroller extends Controller
{
    public function authUser()
    {
        return redirect(route('/login'));
    }
}
