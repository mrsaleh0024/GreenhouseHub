<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('تنظیم رمز عبور جدید')]
class ResetPassword extends Component
{
    public string $mobile = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount()
    {
        $this->mobile = session('reset_mobile', '');

        if (empty($this->mobile)) {
            return redirect()->route('password.request');
        }
    }

    public function resetPassword()
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ]);

        $user = User::where('mobile', $this->mobile)->first();

        if (!$user) {
            $this->addError('password', 'کاربر یافت نشد.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        session()->forget('reset_mobile');

        Auth::login($user);
        session()->regenerate();

        if ($user->hasRole(['super_admin', 'admin'])) {
            return redirect()->intended('/admin');
        }

        if ($user->hasRole('expert')) {
            return redirect()->intended('/expert');
        }

        if ($user->hasRole('greenhouse_owner')) {
            return redirect()->intended('/owner');
        }

        return redirect()->intended('/member');
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}