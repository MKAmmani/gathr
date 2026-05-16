<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->name('register.store');

    // Profile setup routes (registration flow — separate from authenticated /profile)
    Route::get('register/setup', [RegisteredUserController::class, 'showProfile'])
        ->name('profile.show');

    Route::post('register/setup', [RegisteredUserController::class, 'storeProfile'])
        ->name('profile.store');

    // Registration OTP routes
    Route::get('otp', [OtpController::class, 'show'])->name('otp');
    Route::post('otp/verify', [OtpController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('otp.verify');
    Route::post('otp/resend', [OtpController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('otp.resend');

    // Login OTP routes
    Route::get('otp/login', [OtpController::class, 'loginShow'])->name('otp.login');
    Route::post('otp/login/verify', [OtpController::class, 'loginVerify'])
        ->middleware('throttle:10,1')
        ->name('otp.login.verify');
    Route::post('otp/login/resend', [OtpController::class, 'loginResend'])
        ->middleware('throttle:3,1')
        ->name('otp.login.resend');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
