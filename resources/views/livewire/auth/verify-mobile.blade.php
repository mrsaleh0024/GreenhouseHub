<div>
    <h1 class="mb-1 text-center text-xl font-bold text-gray-800">تأیید شماره موبایل</h1>
    <p class="mb-6 text-center text-sm text-gray-500">
        کد {{ \App\Services\OtpService::LENGTH }} رقمی به شماره
        <span dir="ltr" class="font-medium text-gray-700">{{ $maskedMobile }}</span> ارسال شد.
    </p>

    @if (session('otp_error'))
        <x-auth.alert type="error">{{ session('otp_error') }}</x-auth.alert>
    @endif

    <form wire:submit="verify" class="space-y-4">
        <x-auth.input label="کد تأیید" name="code" wire:model="code" ltr inputmode="numeric"
                      autocomplete="one-time-code" maxlength="6" placeholder="------"
                      class="text-center text-lg tracking-[0.5em]" autofocus />

        <x-auth.button target="verify">
            <span wire:loading.remove wire:target="verify">تأیید و ادامه</span>
            <span wire:loading wire:target="verify">در حال بررسی...</span>
        </x-auth.button>
    </form>

    <div class="mt-4">
        <x-otp-resend :seconds="$resendIn" />
    </div>

    <p class="mt-6 border-t border-gray-100 pt-4 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" class="text-green-700 hover:underline">بازگشت به صفحه ورود</a>
    </p>
</div>
