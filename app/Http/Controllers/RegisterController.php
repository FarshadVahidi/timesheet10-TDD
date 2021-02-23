<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            }elseif(request()->user()->hasRole('administrator'))
            {
                return view('admin.registration');
            }
        }else{
            return redirect(route('login'));
        }
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        if($user->isAbleTo('users-create') && $user->hasRole('superadministrator') || $user->hasRole('administrator'))
        {
            $data = $request->validate([
                'name'=>'required|string|max:255',
                'email'=>'required|string|email|unique:users',
                'password'=> 'required|min:8',
                'role_id'=> 'required|string',
            ]);


            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();
            $user->attachRole($request->role_id);

            return back()->with('USER-ADDED', 'User added successfully.');
        }else{
            return back()->with('RED', 'YOU HAVE NO RIGHT TO ACCESS THIS SECTION!!!');
        }

    }
}
