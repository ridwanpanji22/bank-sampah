<?php

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\DriverController;
use App\Http\Controllers\API\QRcodeGenerateController;
use Illuminate\Auth\Events\Verified;

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

// Email Verification Routes
Route::group(['prefix' => 'email'], function () {
    Route::post('/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent!']);
    })->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');

    Route::get('/verify/{id}/{hash}', function (Request $request) {
        $user = User::findOrFail($request->id);

        if (!hash_equals((string) $request->hash, sha1($user->email))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        $user->markEmailAsVerified();
        return response()->json(['message' => 'Email verified successfully']);
    })->middleware(['signed'])->name('verification.verify');
});

// Admin Routes
Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'role:admin']], function () {
    Route::post('/email/verification', [AdminController::class, 'sendVerificationEmail']);

    Route::get('/transactions', [AdminController::class, 'transactions']);
    Route::post('/transactionsReports', [AdminController::class, 'transactionsReports']);
    Route::post('/salesReports', [AdminController::class, 'salesReports']);

    Route::get('/trash', [AdminController::class, 'trash']);
    Route::post('/trash/create', [AdminController::class, 'createTrash']);
    Route::get('/trash/{id}', [AdminController::class, 'trashDetail']);
    Route::post('/trash/update/{id}', [AdminController::class, 'updateTrash']);
    Route::delete('/trash/{id}', [AdminController::class, 'deleteTrash']);

    Route::get('/sales', [AdminController::class, 'sales']);
    Route::post('/sales/create', [AdminController::class, 'createSale']);
    Route::get('/sales/{id}', [AdminController::class, 'salesDetail']);
    Route::post('/sales/update/{id}', [AdminController::class, 'salesUpdate']);
    Route::delete('/sales/{id}', [AdminController::class, 'salesDelete']);

    Route::post('/register', [AdminController::class, 'register']);
    Route::get('/users', [AdminController::class, 'index']);
    Route::get('/users/{id}', [AdminController::class, 'show']);
    Route::patch('/users/{id}', [AdminController::class, 'update']);
    Route::delete('/users/{id}', [AdminController::class, 'destroy']);
    Route::get('/customers', [AdminController::class, 'customers']);
    Route::get('/drivers', [AdminController::class, 'drivers']);
    Route::get('users/transactions/{id}', [AdminController::class, 'userTransactions']);

    Route::get('/qrcode-generate/{ccm}', [QRcodeGenerateController::class, 'qrcode']);
});

// Auth Routes
Route::group(['prefix' => '/'], function () {
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/cek_user', [AuthController::class, 'cek_user']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Dashboard Routes (Customer)
Route::group(['prefix' => 'dashboard', 'middleware' => ['auth:sanctum', 'role:customer']], function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::post('/schedule', [DashboardController::class, 'createSchedule']);
    Route::get('/schedule', [DashboardController::class, 'createScheduleOneClick']);
    Route::get('/schedule/history', [DashboardController::class, 'history']);
    Route::get('/schedule/history/{id}', [DashboardController::class, 'historyDetail']);
});

// Driver Routes
Route::group(['prefix' => 'driver', 'middleware' => ['auth:sanctum', 'role:driver']], function () {
    Route::get('/trash', [DriverController::class, 'trash']);
    Route::get('/schedules', [DriverController::class, 'index']);
    Route::get('/schedules/history', [DriverController::class, 'history']);
    Route::get('/schedules/history/{id}', [DriverController::class, 'historyDetail']);
    Route::get('/schedules/{id}', [DriverController::class, 'show']);
    Route::post('/schedules/transaction/{id}', [DriverController::class, 'inputTransaction']);
    Route::get('/autoCreateSchedule/{ccm}', [DriverController::class, 'autoCreateSchedule']);
});
