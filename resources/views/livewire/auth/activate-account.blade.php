<div>
    <h1 class="mb-1 text-center text-xl font-bold text-gray-800">فعال‌سازی حساب</h1>

    @if ($step === 'mobile')
        <p class="mb-6 text-center text-sm text-gray-500">
            شماره موبایلی را که مدیر ارشد برای شما ثبت کرده وارد کنید تا کد تأیید دریافت کنید.
        </p>

        <form wire:submit="sendCode" class="space-y-4">
            <x-auth.input label="شماره موبایل" name="mobile" type="tel" wire:model="mobile" ltr
                          inputmode="numeric" placeholder="09123456789" autofocus />

            <x-auth.button target="sendCode">
                <span wire:loading.remove wire:target="sendCode">ارسال کد تأیید</span>
                <span wire:loading wire:target="sendCode">در حال ارسال...</span>
            </x-auth.button>
        </form>
    @elseif ($step === 'code')
        <p class="mb-6 text-center text-sm text-gray-500">
            اگر حسابی برای فعال‌سازی آماده باشد، کد تأیید به
            <span dir="ltr" class="font-medium text-gray-700">{{ \App\Support\Mobile::mask($confirmedMobile) }}</span>
            ارسال می‌شود.
        </p>

        <form wire:submit="verifyCode" class="space-y-4">
            <x-auth.input label="کد تأیید" name="code" wire:model="code" ltr inputmode="numeric"
                          autocomplete="one-time-code" maxlength="6" placeholder="------"
                          class="text-center text-lg tracking-[0.5em]" autofocus />

            <x-auth.button target="verifyCode">
                <span wire:loading.remove wire:target="verifyCode">تأیید کد</span>
                <span wire:loading wire:target="verifyCode">در حال بررسی...</span>
            </x-auth.button>
        </form>

        <div class="mt-4">
            <x-otp-resend :seconds="$resendIn" />
        </div>
    @else
        <p class="mb-6 text-center text-sm text-gray-500">شماره شما تأیید شد. رمز عبور خود را تعیین کنید.</p>

        <form wire:submit="setPassword" class="space-y-4">
            <x-auth.input label="رمز عبور" name="password" type="password" wire:model="password" ltr
                          autocomplete="new-password" hint="حداقل ۸ کاراکتر، شامل حرف و عدد" autofocus />
            <x-auth.input label="تکرار رمز عبور" name="password_confirmation" type="password"
                          wire:model="password_confirmation" ltr autocomplete="new-password" />

            <x-auth.button target="setPassword">
                <span wire:loading.remove wire:target="setPassword">فعال‌سازی حساب</span>
                <span wire:loading wire:target="setPassword">در حال ذخیره...</span>
            </x-auth.button>
        </form>
    @endif

    <p class="mt-6 border-t border-gray-100 pt-4 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" class="text-green-700 hover:underline">بازگشت به صفحه ورود</a>
    </p>
</div>
