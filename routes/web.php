<?php

use App\Http\Controllers\DashboardController;
use App\Livewire\Actions\Logout;
use App\Livewire\Admin\AdminManager;
use App\Livewire\Admin\CorrectionInbox;
use App\Livewire\Auth\ActivateAccount;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Auth\VerifyMobile;
use App\Livewire\Greenhouse\Cultivation;
use App\Livewire\Greenhouse\RecordWizard;
use App\Livewire\Greenhouse\Zones;
use App\Livewire\SystemStatus;
use Illuminate\Support\Facades\Route;

Route::get('/', SystemStatus::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/verify-mobile', VerifyMobile::class)->name('verify-mobile');
    Route::get('/activate', ActivateAccount::class)->name('activate');
    Route::get('/two-factor', TwoFactorChallenge::class)->name('two-factor');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password', ResetPassword::class)->name('password.reset');
});

Route::middleware(['auth', 'active'])->group(function () {
    // One shared layout for every role; the menu differs per role (config/navigation.php).
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/greenhouses/{greenhouse}/complete', RecordWizard::class)->name('greenhouses.complete');
    Route::get('/greenhouses/{greenhouse}/cultivation', Cultivation::class)->name('greenhouses.cultivation');
    Route::get('/greenhouses/{greenhouse}/zones', Zones::class)->name('greenhouses.zones');
    Route::get('/admin/correction-requests', CorrectionInbox::class)->name('admin.corrections');

    Route::post('/logout', function (Logout $logout) {
        $logout();

        return redirect()->route('login');
    })->name('logout');

    Route::middleware('role:super_admin')->prefix('admin')->group(function () {
        Route::get('/admins', AdminManager::class)->name('admin.admins');
    });
});
