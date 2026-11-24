<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">

        <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">تأیید شماره موبایل</h2>
        <p class="text-center text-gray-500 text-sm mb-6">{{ $mobile }}</p>

        {{-- مرحله ۱: ارسال OTP --}}
        @if (!$otpSent)
            <div class="space-y-4">
                <p class="text-sm text-gray-600 text-center">
                    برای ادامه، کد تأیید به شماره موبایل شما ارسال می‌شود.
                </p>

                <button wire:click="sendOtp"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 rounded-lg transition">
                    ارسال کد تأیید
                </button>

                @error('mobile')
                    <span class="text-red-500 text-sm block text-center">{{ $message }}</span>
                @enderror
            </div>
        @endif

        {{-- مرحله ۲: وارد کردن OTP --}}
        @if ($otpSent && !$otpVerified)
            <form wire:submit="verifyOtp" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">کد تأیید ۶ رقمی</label>
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
                        class="w-full text-sm text-gray-500 hover:text-green-600 mt-2">
                    ارسال مجدد کد
                </button>
            </form>
        @endif

        {{-- مرحله ۳: تعیین رمز عبور --}}
        @if ($otpVerified)
            <form wire:submit="setPassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رمز عبور جدید</label>
                    <input type="password"
                           wire:model="password"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500">
                    @error('password') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تکرار رمز عبور</label>
                    <input type="password"
                           wire:model="password_confirmation"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500">
                </div>

                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 rounded-lg transition">
                    ثبت رمز و ورود
                </button>
            </form>
        @endif

    </div>
</div>