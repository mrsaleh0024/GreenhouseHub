<div>
    <h1 class="mb-1 text-center text-xl font-bold text-gray-800">فراموشی رمز عبور</h1>

    @if ($step === 'mobile')
        <p class="mb-6 text-center text-sm text-gray-500">شماره موبایل خود را وارد کنید تا کد بازیابی برایتان پیامک شود.</p>

        <form wire:submit="sendCode" class="space-y-4">
            <x-auth.input label="شماره موبایل" name="mobile" type="tel" wire:model="mobile" ltr
                          inputmode="numeric" placeholder="09123456789" autofocus />

            <x-auth.button target="sendCode">
                <span wire:loading.remove wire:target="sendCode">ارسال کد</span>
                <span wire:loading wire:target="sendCode">در حال ارسال...</span>
            </x-auth.button>
        </form>
    @else
        <p class="mb-6 text-center text-sm text-gray-500">
            اگر این شماره در سامانه ثبت شده باشد، کد به
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
    @endif

    <p class="mt-6 border-t border-gray-100 pt-4 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" class="text-green-700 hover:underline">بازگشت به صفحه ورود</a>
    </p>
</div>
