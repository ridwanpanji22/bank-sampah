<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\AdminController;
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

Route::get('/admin/transactions', [AdminController::class, 'transactions'])->middleware(['auth:sanctum', 'role:admin']);
Route::post('/admin/register', [AdminController::class, 'register'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('/admin/users', [AdminController::class, 'index'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('/admin/customers', [AdminController::class, 'customers'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('/admin/drivers', [AdminController::class, 'drivers'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('admin/users/transactions/{id}', [AdminController::class, 'userTransactions'])->middleware(['auth:sanctum', 'role:admin']);
Route::get('/admin/users/{id}', [AdminController::class, 'show'])->middleware(['auth:sanctum', 'role:admin']);
Route::delete('/admin/users/{id}', [AdminController::class, 'destroy'])->middleware(['auth:sanctum', 'role:admin']);
Route::patch('/admin/users/{id}', [AdminController::class, 'update'])->middleware(['auth:sanctum', 'role:admin']);

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth:sanctum', 'role:customer']);
Route::post('/dashboard/schedule', [DashboardController::class, 'createSchedule'])->middleware(['auth:sanctum', 'role:customer']);
Route::get('/dashboard/schedule/', [DashboardController::class, 'statusSchedule'])->middleware(['auth:sanctum', 'role:customer']);
Route::get('/dashboard/schedule/history', [DashboardController::class, 'history'])->middleware(['auth:sanctum', 'role:customer']);
Route::get('/dashboard/schedule/history/{id}', [DashboardController::class, 'historyDetail'])->middleware(['auth:sanctum', 'role:customer']);

Route::get('/driver/schedules', [DriverController::class, 'index'])->middleware(['auth:sanctum', 'role:driver']);
Route::get('/driver/schedules/history', [DriverController::class, 'history'])->middleware(['auth:sanctum', 'role:driver']);
Route::get('/driver/schedules/history/{id}', [DriverController::class, 'historyDetail'])->middleware(['auth:sanctum', 'role:driver']);
Route::get('/driver/schedules/{id}', [DriverController::class, 'show'])->middleware(['auth:sanctum', 'role:driver']);
Route::get('/driver/schedules/pickup/{id}', [DriverController::class, 'pickup'])->middleware(['auth:sanctum', 'role:driver']);
Route::post('/driver/schedules/transaction/{id}', [DriverController::class, 'inputTransaction'])->middleware(['auth:sanctum', 'role:driver']);