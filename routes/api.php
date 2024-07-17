<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\http\Controllers\API\AdminController;
use App\Http\Controllers\API\DriverController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [AuthController::class, 'login']);
Route::get('/cek_user', [AuthController::class, 'cek_user']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::post('/admin/register', [AdminController::class, 'register'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('/admin/users', [AdminController::class, 'index'])->middleware(['auth:sanctum', 'role:admin']);

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth:sanctum', 'role:customer']);
Route::post('/dashboard/schedule', [DashboardController::class, 'createSchedule'])->middleware(['auth:sanctum', 'role:customer']);

Route::get('/driver/schedules', [DriverController::class, 'index'])->middleware(['auth:sanctum', 'role:driver']);
Route::get('/driver/schedules/{id}', [DriverController::class, 'show'])->middleware(['auth:sanctum', 'role:driver']);
Route::get('/driver/schedules/pickup/{id}', [DriverController::class, 'pickup'])->middleware(['auth:sanctum', 'role:driver']);