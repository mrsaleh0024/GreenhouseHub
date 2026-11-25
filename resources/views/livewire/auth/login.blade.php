<div>
    <h1 class="mb-1 text-center text-xl font-bold text-gray-800">ورود به سامانه</h1>
    <p class="mb-6 text-center text-sm text-gray-500">با شماره موبایل و رمز عبور وارد شوید.</p>

    @if (session('status'))
        <x-auth.alert>{{ session('status') }}</x-auth.alert>
    @endif

    <form wire:submit="login" class="space-y-4">
        <x-auth.input label="شماره موبایل" name="mobile" type="tel" wire:model="mobile" ltr
                      inputmode="numeric" autocomplete="username" placeholder="09123456789" autofocus />

        <x-auth.input label="رمز عبور" name="password" type="password" wire:model="password" ltr
                      autocomplete="current-password" />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-gray-600">
                <input type="checkbox" wire:model="remember" class="rounded border-gray-300 text-green-600 focus:ring-green-600">
                مرا به خاطر بسپار
            </label>
            <a href="{{ route('password.request') }}" class="text-green-700 hover:underline">فراموشی رمز عبور</a>
        </div>

        <x-auth.button target="login">
            <span wire:loading.remove wire:target="login">ورود</span>
            <span wire:loading wire:target="login">در حال بررسی...</span>
        </x-auth.button>
    </form>

    <div class="mt-6 space-y-2 border-t border-gray-100 pt-4 text-center text-sm text-gray-600">
        <p>حساب کاربری ندارید؟ <a href="{{ route('register') }}" class="font-medium text-green-700 hover:underline">ثبت‌نام</a></p>
        <p>حساب شما را مدیر ساخته است؟ <a href="{{ route('activate') }}" class="font-medium text-green-700 hover:underline">فعال‌سازی حساب</a></p>
    </div>
</div>
