<?php

namespace App\Http\Controllers;

use App\Models\Hour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class HourController extends Controller
{
    public function routeCheck()
    {
        $user = Auth::user();
        if($user->isSuper())
        {
            return view('super.addHour');
        }elseif($user->isAdmin()){
            return view('admin.addHour');
        }else
        {
            return redirect(route('login'));
        }

    }

    public function index()
    {
        $user = Auth::user();
        if($user->isAbleTo('hour-read'))
        {
            if($user->hasRole('superadministrator'))
            {
                $allMyHours = request()->user()->userHours();
                return view('super.allHours', compact('allMyHours'));
            }
        }else{
            return redirect()->route('login');
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if($user->isAbleTo('hour-create')) {
            $entry = Hour::where('user_id',  $request->user()->id)->where('date', $request->date)->first();
            if (null === $entry)
            {
                try{
                    $hour = new Hour();
                    $hour->user_id = $request->user()->id;
                    $hour->date = $request->date;
                    $this->checkferie($hour, $request);
                    $hour->save();
                    return redirect()->back()->with('ADDED', 'DATE AND HOUR HAS BEEN ADDED SUCCESSFULLY.');
                }catch(\Exception $exception){
                    return Redirect::back()->withErrors(['MSG', 'THERE WAS PROBLEM AND YOU DATA DOES NOT ADDED TO DATABASE!']);
                }
            }else{
                return redirect(route('add'))->with('DUPLICATE', 'THE ENTERED DATE EXIST');
            }

        }else{
            return redirect()->back()->with('RED', 'YOU HAVE NO RIGHT TO ACCESS THIS SECTION!!!');
        }
    }

    public function edit($id)
    {
        $user = Auth::user();

        if($user->isAbleTo('hour-update'))
        {
          $date = Hour::find($id);

          if($date === null)
              return redirect()->back()->with('NOTEXIST', 'DATA DOES NOT EXIST!');

          if($user->hasRole('superadministrator'))
          {
              if($user->id == $date->user_id)
                return view('super.edit-hour', compact('date'));
              else
                  return view('super.edit-staff-hour', compact('date'));

          }elseif($user->hasRole('administrator')) {
              if ($user->id == $date->user_id)
                  return view('admin.edit-hour', compact('date'));
              else
                  return redirect()->back()->with('ALERT', 'YOU HAVE NO PERMISSION TO ACCESS!!!');
          }
        }else {
              return redirect(route('login'));
        }
    }


    public function staffHour()
    {
        $user = Auth::user();
        if($user->hasRole('superadministrator') && $user->isAbleTo('hour-read'))
        {
            $staffHour = DB::table('users')->join('hours', 'users.id' , '=', 'hours.user_id')->select('users.id', 'users.name', DB::raw('sum(hour) as sum'))
                ->groupBy('users.id')->orderByRaw('user_id ASC')->get();
            return view('super.staffHour', compact('staffHour'));
        }
    }


    public function show($id)
    {
        $user = Auth::user();
        if($user->hasRole('superadministrator'))
        {
            $data = $user->seeDetailHour($id);
            return view ('super.hourdetail', compact('data'));
        }else{
            return back()->with('hasNotPermission', 'YOU DO NOT HAVE ACCESS TO THIS SECTION!!!');
        }
    }


    public function destroy($id): \Illuminate\Http\RedirectResponse
    {
        if(Auth::user()->hasRole('superadministrator'))
        {
            Hour::where('id', $id)->delete();
            return back()->with('hour_deleted', 'Hour has been deleted successfully!');

        }else
            return back()->with('alert_deleted', 'You do not have access to delete hour');

    }




    /**
     * @param Hour $hour
     * @param Request $request
     */
    private function checkferie(Hour $hour, Request $request): void
    {
        if ($request->nonWork === 0) {
            $hour->hour = $request->hour;
            $hour->ferie = false;
        }
        else{
            $hour->ferie = true;
            $hour->hour = 0;
        }

    }
}
