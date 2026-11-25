<div>
    <h1 class="mb-1 text-center text-xl font-bold text-gray-800">رمز عبور جدید</h1>
    <p class="mb-6 text-center text-sm text-gray-500">رمز عبور جدید خود را وارد کنید.</p>

    <form wire:submit="resetPassword" class="space-y-4">
        <x-auth.input label="رمز عبور جدید" name="password" type="password" wire:model="password" ltr
                      autocomplete="new-password" hint="حداقل ۸ کاراکتر، شامل حرف و عدد" autofocus />
        <x-auth.input label="تکرار رمز عبور" name="password_confirmation" type="password"
                      wire:model="password_confirmation" ltr autocomplete="new-password" />

        <x-auth.button target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">ذخیره رمز عبور</span>
            <span wire:loading wire:target="resetPassword">در حال ذخیره...</span>
        </x-auth.button>
    </form>
</div>
