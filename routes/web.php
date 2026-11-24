<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\VerifyMobile;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/verify-mobile', VerifyMobile::class)->name('verify-mobile');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password', ResetPassword::class)->name('password.reset');
});