<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">

        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">فراموشی رمز عبور</h2>

        @if (!$otpSent)
            <form wire:submit="sendOtp" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل</label>
                    <input type="text"
                           wire:model="mobile"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500"
                           placeholder="09123456789"
                           dir="ltr">
                    @error('mobile') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 rounded-lg transition">
                    ارسال کد تأیید
                </button>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-green-600">
                        بازگشت به صفحه ورود
                    </a>
                </div>
            </form>
        @else
            <form wire:submit="verifyOtp" class="space-y-5">
                <p class="text-sm text-gray-600 text-center mb-4">
                    کد تأیید به شماره <strong>{{ $mobile }}</strong> ارسال شد.
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">کد ۶ رقمی</label>
                    <input type="text"
                           wire:model="otp"
                           maxlength="6"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-center tracking-widest text-lg focus:ring-2 focus:ring-green-500"
                           placeholder="------"
                           dir="ltr">
                    @error('otp') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 rounded-lg transition">
                    تأیید کد
                </button>

                <button type="button" wire:click="sendOtp"
                        class="w-full text-sm text-gray-500 hover:text-green-600">
                    ارسال مجدد کد
                </button>
            </form>
        @endif

    </div>
</div>