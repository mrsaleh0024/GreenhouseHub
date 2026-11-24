<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">ورود به سامانه</h2>

        <form wire:submit="login" class="space-y-5">
            {{-- موبایل --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل</label>
                <input type="text"
                       wire:model="mobile"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                       placeholder="09123456789"
                       dir="ltr">
                @error('mobile') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- رمز عبور --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">رمز عبور</label>
                <input type="password"
                       wire:model="password"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                @error('password') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="remember" class="rounded border-gray-300 text-green-600">
                    <span class="text-sm text-gray-600">مرا به خاطر بسپار</span>
                </label>

                <a href="{{ route('password.request') }}" class="text-sm text-green-600 hover:underline">
                    فراموشی رمز عبور؟
                </a>
            </div>

            <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 rounded-lg transition">
                ورود
            </button>
        </form>
    </div>
</div>