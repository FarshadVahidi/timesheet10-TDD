<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HourController;
use App\Http\Controllers\RegisterController;
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

    Route::get('/hour-update/{id}', [HourController::class, 'edit']);

    Route::get('/allMyHours', [hourController::class, 'index'])->name('Myhours');

    Route::get('/addNewPerson', [RegisterController::class, 'index'])->name('register');
    Route::post('/addNewPerson', [RegisterController::class, 'store']);

    Route::get('/staffHour', [HourController::class, 'staffHour'])->name('staffHour');

    Route::get('/hours-detail/{id}', [HourController::class, 'show'])->name('hours-detail');
    Route::get('/hours-update/{id}', [HourController::class, 'edit']);
    Route::get('/hours-delete/{id}', [HourController::class, 'destroy']);
});
