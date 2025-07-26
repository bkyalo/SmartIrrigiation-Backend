<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Swagger UI Route (Accessible without authentication)
Route::get('/api/documentation', '\L5Swagger\Http\Controllers\SwaggerController@api')->name('l5swagger.api');
Route::get('/api/docs', '\L5Swagger\Http\Controllers\SwaggerController@docs')->name('l5swagger.docs');
Route::get('/api/oauth2-callback', '\L5Swagger\Http\Controllers\SwaggerController@oauth2Callback')->name('l5swagger.oauth2_callback');

// Dashboard Route (Requires authentication)
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TankController;
use App\Http\Controllers\PumpController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Resource Routes (Requires authentication)
Route::middleware(['auth', 'verified'])->group(function () {
    // Tank Management
    Route::resource('tanks', TankController::class)->except(['show']);
    Route::get('tanks/{tank}', [TankController::class, 'show'])->name('tanks.show');
    
    // Pump Management
    Route::resource('pumps', PumpController::class);
    Route::patch('pumps/{pump}/toggle-status', [PumpController::class, 'toggleStatus'])->name('pumps.toggle-status');
});

// Profile Routes (Require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Authentication Routes
require __DIR__.'/auth.php';
