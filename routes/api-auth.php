<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
 
// Authentication Routes

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/set-password-email-link', [AuthController::class, 'setPasswordEmailLink']);

    Route::post('/reset-password', [AuthController::class, 'resetPassword']);  
    Route::post('/reset-otp', [AuthController::class, 'resetOtp']);  
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

Route::get('/encrypt-password', [AuthController::class, 'encryptPassword']);



