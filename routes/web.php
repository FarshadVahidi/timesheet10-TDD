<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HourController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
//    return view('dashboard');
//})->name('dashboard');

Route::group(['middleware' => ['auth:sanctum', 'verified']], function(){
        Route::get('/dashboard', [DashboardController::class, 'authUser'])->name('dashboard');

    Route::get('/addNewHour',[HourController::class,'routeCheck'])->name('add');
    Route::post('/createNewHour', [HourController::class,'store']);
});
