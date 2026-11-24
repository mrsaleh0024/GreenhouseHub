<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">

        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">تنظیم رمز عبور جدید</h2>

        <form wire:submit="resetPassword" class="space-y-5">
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
                تغییر رمز عبور و ورود
            </button>
        </form>

    </div>
</div>