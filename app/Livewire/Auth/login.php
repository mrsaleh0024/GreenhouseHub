<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('ورود به سامانه')]
class Login extends Component
{
    public string $mobile = '';
    public string $password = '';
    public bool $remember = false;

    protected function rules()
    {
        return [
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    protected $messages = [
        'mobile.required' => 'شماره موبایل الزامی است.',
        'mobile.regex' => 'لطفا یک شماره موبایل معتبر وارد کنید ',
        'password.required' => 'رمز عبور الزامی است.',
    ];

    public function login()
    {
        $this->validate();

        $user = User::where('mobile', $this->mobile)->first();

        if (!$user) {
            $this->addError('mobile', 'کاربری با این شماره موبایل یافت نشد.');
            return;
        }

        if (!$user->isMobileVerified()) {
            session(['pending_mobile' => $this->mobile]);
            return redirect()->route('verify-mobile');
        }

        if ($user->status !== 'active') {
            $this->addError('mobile', 'حساب کاربری شما غیرفعال است.');
            return;
        }

        if (!Auth::attempt(['mobile' => $this->mobile, 'password' => $this->password], $this->remember)) {
            $this->addError('password', 'رمز عبور اشتباه است.');
            return;
        }

        session()->regenerate();

        // هدایت بر اساس نقش
        return $this->redirectBasedOnRole($user);
    }

    private function redirectBasedOnRole(User $user)
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return redirect()->intended('/admin');
        }

        if ($user->hasRole('expert')) {
            return redirect()->intended('/expert');
        }

        if ($user->hasRole('greenhouse_owner')) {
            return redirect()->intended('/owner');
        }

        // member
        return redirect()->intended('/member');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}