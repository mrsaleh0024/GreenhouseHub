<div>
    <h1 class="mb-1 text-center text-xl font-bold text-gray-800">ثبت‌نام در سامانه</h1>
    <p class="mb-6 text-center text-sm text-gray-500">نوع حساب خود را انتخاب کنید و اطلاعات را تکمیل کنید.</p>

    <form wire:submit="register" class="space-y-6">
        {{-- Account type --}}
        <fieldset>
            <legend class="mb-2 text-sm font-medium text-gray-700">نوع حساب</legend>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['greenhouse_owner', 'گلخانه‌دار', 'مالک یا مدیر گلخانه؛ مشاهده پرونده و گزارش‌های کارشناسی'],
                    ['expert', 'کارشناس', 'ثبت بازدید و گزارش‌های تخصصی برای مشتریان'],
                ] as [$value, $title, $desc])
                    <label class="cursor-pointer">
                        <input type="radio" wire:model.live="role" value="{{ $value }}" class="peer sr-only">
                        <div class="h-full rounded-xl border-2 border-gray-200 p-4 transition peer-checked:border-green-600 peer-checked:bg-green-50 peer-focus-visible:ring-2 peer-focus-visible:ring-green-600">
                            <div class="font-semibold text-gray-800">{{ $title }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ $desc }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        {{-- Account information --}}
        <fieldset class="space-y-4">
            <legend class="mb-1 text-sm font-semibold text-gray-700">اطلاعات حساب</legend>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-auth.input label="نام و نام خانوادگی" name="name" wire:model="name" autocomplete="name" />
                <x-auth.input label="شماره موبایل" name="mobile" type="tel" wire:model="mobile" ltr
                              inputmode="numeric" autocomplete="username" placeholder="09123456789" />
                <x-auth.input label="رمز عبور" name="password" type="password" wire:model="password" ltr
                              autocomplete="new-password" hint="حداقل ۸ کاراکتر، شامل حرف و عدد" />
                <x-auth.input label="تکرار رمز عبور" name="password_confirmation" type="password"
                              wire:model="password_confirmation" ltr autocomplete="new-password" />
            </div>
        </fieldset>

        {{-- Role specific information --}}
        @if ($role === 'greenhouse_owner')
            <fieldset class="space-y-4">
                <legend class="mb-1 text-sm font-semibold text-gray-700">اطلاعات گلخانه</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-auth.input label="نام گلخانه" name="greenhouse_name" wire:model="greenhouse_name" />
                    </div>

                    <div>
                        <label for="province" class="mb-1 block text-sm font-medium text-gray-700">استان</label>
                        <select id="province" wire:model="province_id"
                                class="block w-full rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:border-green-600 focus:ring-green-600">
                            <option value="">انتخاب کنید</option>
                            @foreach ($provinces as $item)
                                <option value="{{ $item->id }}">{{ $item->label_fa }}</option>
                            @endforeach
                        </select>
                        @error('province_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <x-auth.input label="شهر" name="city" wire:model="city" />
                </div>
            </fieldset>
        @else
            <fieldset class="space-y-4">
                <legend class="mb-1 text-sm font-semibold text-gray-700">اطلاعات تخصصی</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-auth.input label="تخصص" name="specialty" wire:model="specialty"
                                      placeholder="مثلاً بیماری‌شناسی گیاهی، تغذیه و کوددهی" />
                    </div>

                    <x-auth.input label="شماره نظام/مجوز (اختیاری)" name="license_number"
                                  wire:model="license_number" ltr />
                    <x-auth.input label="سابقه‌ی کار (سال)" name="experience_years" type="number"
                                  wire:model="experience_years" ltr min="0" max="60" inputmode="numeric" />
                </div>
            </fieldset>
        @endif

        <x-auth.button target="register">
            <span wire:loading.remove wire:target="register">ثبت‌نام و دریافت کد تأیید</span>
            <span wire:loading wire:target="register">در حال ثبت‌نام...</span>
        </x-auth.button>
    </form>

    <p class="mt-6 border-t border-gray-100 pt-4 text-center text-sm text-gray-600">
        قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ route('login') }}" class="font-medium text-green-700 hover:underline">ورود</a>
    </p>
</div>
