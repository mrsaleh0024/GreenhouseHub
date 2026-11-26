<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
    <a wire:navigate href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-green-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-700">
        <span aria-hidden="true">→</span> بازگشت به داشبورد
    </a>

    <div class="mt-7 grid gap-8 lg:grid-cols-[17rem_minmax(0,1fr)] lg:gap-12">
        <aside aria-label="مراحل تکمیل پرونده" class="lg:sticky lg:top-8 lg:self-start">
            <p class="text-xs font-semibold text-green-700">پرونده {{ $greenhouse->greenhouse_code }}</p>
            <h1 class="mt-3 text-2xl font-bold leading-10 text-gray-900">{{ $greenhouse->name }}</h1>
            <p class="mt-2 text-sm leading-7 text-gray-600">لازم نیست همه اطلاعات را همین حالا وارد کنید. هر بخش جداگانه ذخیره می‌شود.</p>
            <ol class="mt-7 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-1 lg:gap-3">
                @foreach ([1 => 'اطلاعات پایه', 2 => 'ساختار و کشت', 3 => 'سامانه‌ها', 4 => 'ادامه پرونده'] as $number => $label)
                    <li @if ($step === $number) aria-current="step" @endif class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm {{ $step === $number ? 'bg-green-700 font-semibold text-white' : 'text-gray-600' }}">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full {{ $step === $number ? 'bg-white/20' : 'bg-white text-green-700' }}">{{ $number }}</span>
                        <span>{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
            <p class="mt-5 text-xs leading-6 text-gray-500">مرحله {{ $step }} از ۴</p>
        </aside>

        <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="step-heading">
            @if ($saved)
                <div role="status" class="mb-6 rounded-xl bg-green-50 px-4 py-3 text-sm leading-6 text-green-800">اطلاعات بخش قبل ذخیره شد.</div>
            @endif
            @if ($customNotice)<div role="status" class="mb-6 rounded-xl bg-green-50 px-4 py-3 text-sm leading-6 text-green-800">{{ $customNotice }}</div>@endif
            @if (auth()->user()->hasAnyRole(['admin', 'super_admin']) && $step === 1)
                <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <h2 class="text-sm font-bold text-gray-900">اصلاح موقعیت اصلی گلخانه</h2>
                    <p class="mt-1 text-xs leading-6 text-gray-600">این اطلاعات کلیدی فقط توسط مدیر قابل اصلاح است.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div><label for="admin-province" class="block text-sm font-semibold">استان</label><select id="admin-province" wire:model.live="provinceId" class="mt-2 w-full rounded-lg border-gray-300 text-sm"><option value="">انتخاب استان</option>@foreach ($provinces as $province)<option value="{{ $province->id }}">{{ $province->label_fa }}</option>@endforeach</select>@error('province_id')<p role="alert" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                        <div><label for="admin-city" class="block text-sm font-semibold">شهر</label><select id="admin-city" wire:model="cityId" class="mt-2 w-full rounded-lg border-gray-300 text-sm"><option value="">انتخاب شهر</option>@foreach ($cities as $city)<option value="{{ $city->id }}">{{ $city->label_fa }}</option>@endforeach</select>@error('city_id')<p role="alert" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                    </div>
                    <button type="button" wire:click="correctLocation" wire:loading.attr="disabled" wire:target="correctLocation" class="mt-4 rounded-lg border border-green-700 px-4 py-2 text-sm font-semibold text-green-700 disabled:opacity-60">ذخیره موقعیت</button>
                </div>
            @endif
            @if (auth()->user()->hasRole('greenhouse_owner') && $step === 1)
                <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm leading-7 text-gray-700">
                    <p>برای اصلاح شماره تماس ثبت‌نام، مالکیت یا استان و شهر، درخواست خود را برای مدیر بفرستید.</p>
                    <button type="button" wire:click="$toggle('showCorrection')" class="mt-2 font-semibold text-green-700 hover:underline">{{ $showCorrection ? 'بستن فرم درخواست' : 'درخواست اصلاح اطلاعات کلیدی' }}</button>
                    @if ($showCorrection)
                        <label for="key-correction" class="mt-4 block font-semibold">توضیح درخواست</label>
                        <textarea id="key-correction" wire:model="correctionDetails" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-green-600 focus:ring-green-600"></textarea>
                        @error('correctionDetails')<p role="alert" class="mt-2 text-red-700">{{ $message }}</p>@enderror
                        <button type="button" wire:click="requestCorrection" wire:loading.attr="disabled" wire:target="requestCorrection" class="mt-3 rounded-lg bg-green-700 px-4 py-2 font-semibold text-white disabled:opacity-60">ارسال درخواست</button>
                    @endif
                    @foreach ($corrections as $correction)
                        <div class="mt-4 border-t border-gray-200 pt-3"><p class="font-semibold">{{ $correction->status === 'pending' ? 'در انتظار بررسی' : 'بررسی‌شده' }}</p><p class="whitespace-pre-wrap">{{ $correction->details }}</p>@if ($correction->resolution)<p>پاسخ مدیر: {{ $correction->resolution }}</p>@endif</div>
                    @endforeach
                </div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-sm leading-6 text-red-700">برخی اطلاعات نیاز به بررسی دارند. پیام کنار هر بخش را ببینید.</div>
            @endif

            @if ($step === 1)
                <p class="text-xs font-semibold text-green-700">بخش اول</p>
                <h2 id="step-heading" class="mt-2 text-xl font-bold text-gray-900">با گلخانه شما آشنا شویم</h2>
                <p class="mt-2 text-sm leading-7 text-gray-600">نام گلخانه برای شناسایی پرونده لازم است؛ بقیه موارد را هر زمان خواستید کامل کنید.</p>
                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label for="record-name" class="block text-sm font-semibold text-gray-800">نام گلخانه <span class="text-red-700">*</span></label><input id="record-name" wire:model="name" aria-required="true" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror class="mt-2 w-full rounded-xl border-gray-300 px-4 py-3 text-sm focus:border-green-600 focus:ring-green-600">@error('name') <p id="name-error" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror</div>
                    <div><label for="record-area" class="block text-sm font-semibold text-gray-800">مساحت گلخانه</label><div class="relative mt-2"><input id="record-area" type="number" min="0.01" step="0.01" inputmode="decimal" wire:model="area_m2" aria-describedby="area-hint" class="w-full rounded-xl border-gray-300 px-4 py-3 pe-20 text-sm focus:border-green-600 focus:ring-green-600"><span class="absolute inset-y-0 end-4 flex items-center text-xs text-gray-500">متر مربع</span></div><p id="area-hint" class="mt-2 text-xs leading-6 text-gray-500">مساحت تقریبی را هم می‌توانید ثبت کنید.</p>@error('area_m2') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror</div>
                    <div><livewire:shared.jalali-picker wire:model="commissioned_on" label="تاریخ شروع بهره‌برداری" /><p id="commissioned-hint" class="mt-2 text-xs leading-6 text-gray-500">اختیاری، به تاریخ شمسی: سال/ماه/روز</p>@error('commissioned_on') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror</div>
                    <div class="sm:col-span-2"><label for="record-address" class="block text-sm font-semibold text-gray-800">نشانی دقیق</label><textarea id="record-address" rows="3" wire:model="address" class="mt-2 w-full rounded-xl border-gray-300 px-4 py-3 text-sm focus:border-green-600 focus:ring-green-600" placeholder="نشانی، مسیر دسترسی یا نشانه‌ای نزدیک گلخانه"></textarea>@error('address') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror</div>
                </div>
                <details class="mt-7 rounded-xl bg-gray-50 p-4"><summary class="cursor-pointer text-sm font-semibold text-gray-800">موقعیت جغرافیایی و ارتفاع <span class="font-normal text-gray-500">(اختیاری)</span></summary><p class="mt-3 text-xs leading-6 text-gray-600">اگر مختصات را می‌دانید، عرض و طول را با هم وارد کنید.</p><div class="mt-4 grid gap-4 sm:grid-cols-3">@foreach (['latitude' => 'عرض جغرافیایی', 'longitude' => 'طول جغرافیایی', 'altitude_m' => 'ارتفاع از سطح دریا (متر)'] as $field => $label)<div><label for="{{ $field }}" class="block text-xs font-semibold text-gray-700">{{ $label }}</label><input id="{{ $field }}" wire:model="{{ $field }}" dir="ltr" inputmode="decimal" class="mt-2 w-full rounded-lg border-gray-300 text-sm focus:border-green-600 focus:ring-green-600">@error($field) <p class="mt-2 text-xs text-red-700">{{ $message }}</p> @enderror</div>@endforeach</div></details>
            @elseif ($step === 2)
                <p class="text-xs font-semibold text-green-700">بخش دوم</p><h2 id="step-heading" class="mt-2 text-xl font-bold text-gray-900">ساختار و روش کشت</h2><p class="mt-2 text-sm leading-7 text-gray-600">اگر هنوز مطمئن نیستید، از این بخش عبور کنید و بعداً برگردید.</p>
                @foreach (['structure_type' => ['structure_type_id', 'نوع سازه'], 'cultivation_type' => ['cultivation_type_id', 'روش کشت']] as $type => [$field, $label])
                    <fieldset x-data="catalogFilter" class="mt-8"><legend class="text-sm font-bold text-gray-900">{{ $label }}</legend><input type="search" x-model.debounce.100ms="query" aria-label="جست‌وجو در گزینه‌ها" placeholder="جست‌وجو در گزینه‌ها…" class="mt-4 w-full rounded-xl border-gray-300 px-4 py-2 text-sm focus:border-green-600 focus:ring-green-600"><div class="mt-3 grid gap-3 sm:grid-cols-2">@foreach ($catalog->get($type, collect())->where('is_common', true) as $item)<label x-show="matches($el.textContent)" class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-800 transition hover:bg-green-50"><input type="radio" wire:model="{{ $field }}" value="{{ $item->id }}" class="border-gray-300 text-green-600 focus:ring-green-600"><span>{{ $item->label_fa }}</span></label>@endforeach</div><details x-effect="if (query.length) $el.open = true" class="mt-3 rounded-xl bg-gray-50 p-4" @if ($catalog->get($type, collect())->where('is_common', false)->contains('id', (int) $this->{$field})) open @endif><summary class="cursor-pointer text-sm font-semibold text-green-700">گزینه‌های بیشتر یا ثبت گزینه جدید</summary><div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach ($catalog->get($type, collect())->where('is_common', false) as $item)<label x-show="matches($el.textContent)" class="flex items-start gap-3 text-sm leading-6 text-gray-700"><input type="radio" wire:model="{{ $field }}" value="{{ $item->id }}" class="mt-1 border-gray-300 text-green-600 focus:ring-green-600"><span>{{ $item->label_fa }} @if ($item->status === 'pending') <small class="text-gray-500">(در انتظار بررسی)</small> @endif</span></label>@endforeach</div><div class="mt-5 flex flex-wrap items-end gap-3"><div class="min-w-0 flex-1"><label for="custom-{{ $type }}" class="block text-xs font-semibold text-gray-700">گزینه دیگری دارید؟</label><input id="custom-{{ $type }}" wire:model="customLabels.{{ $type }}" maxlength="150" class="mt-2 w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-green-600 focus:ring-green-600" placeholder="عنوان را بنویسید">@error('customLabels.'.$type)<p class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</div><button type="button" wire:click="addCustom('{{ $type }}')" wire:loading.attr="disabled" class="rounded-lg border border-green-600 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-white disabled:opacity-60">افزودن</button></div></details>@error($field) <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror</fieldset>
                @endforeach
            @elseif ($step === 3)
                <p class="text-xs font-semibold text-green-700">بخش سوم</p><h2 id="step-heading" class="mt-2 text-xl font-bold text-gray-900">سامانه‌های گلخانه</h2><p class="mt-2 text-sm leading-7 text-gray-600">هر تعداد گزینه مرتبط را انتخاب کنید. اگر سیستمی ندارید، فقط «ندارد» را انتخاب کنید.</p>
                @foreach (['irrigation_type' => 'آبیاری', 'heating_system' => 'گرمایش', 'cooling_system' => 'سرمایش', 'water_source' => 'منبع آب', 'ventilation_system' => 'تهویه'] as $type => $label)
                    <details class="mt-4 rounded-xl border border-gray-200 p-4 sm:p-5" @if ($loop->first) open @endif><summary class="cursor-pointer text-sm font-bold text-gray-900">{{ $label }} <span class="font-normal text-gray-500">({{ count($options[$type] ?? []) }} انتخاب)</span></summary><fieldset x-data="catalogFilter" class="mt-4"><legend class="sr-only">{{ $label }}</legend><input type="search" x-model.debounce.100ms="query" aria-label="جست‌وجو در گزینه‌ها" placeholder="جست‌وجو در گزینه‌ها…" class="mt-4 w-full rounded-xl border-gray-300 px-4 py-2 text-sm focus:border-green-600 focus:ring-green-600"><div class="grid gap-3 sm:grid-cols-2">@foreach ($catalog->get($type, collect()) as $item)<label x-show="matches($el.textContent)" class="flex cursor-pointer items-start gap-3 rounded-lg px-2 py-2 text-sm leading-6 text-gray-700 hover:bg-gray-50"><input type="checkbox" wire:model="options.{{ $type }}" value="{{ $item->id }}" class="mt-1 rounded border-gray-300 text-green-600 focus:ring-green-600"><span>{{ $item->label_fa }} @if ($item->status === 'pending') <small class="text-gray-500">(در انتظار بررسی)</small> @endif</span></label>@endforeach</div><div class="mt-4 flex flex-wrap items-end gap-3"><div class="min-w-0 flex-1"><label for="custom-{{ $type }}" class="block text-xs font-semibold text-gray-700">گزینه دیگری دارید؟</label><input id="custom-{{ $type }}" wire:model="customLabels.{{ $type }}" maxlength="150" class="mt-2 w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-green-600 focus:ring-green-600" placeholder="عنوان را بنویسید">@error('customLabels.'.$type)<p class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</div><button type="button" wire:click="addCustom('{{ $type }}')" wire:loading.attr="disabled" class="rounded-lg border border-green-600 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-gray-50 disabled:opacity-60">افزودن</button></div></fieldset></details>
                @endforeach
                @error('options') <p class="mt-4 text-sm text-red-700">{{ $message }}</p> @enderror
            @else
                <div class="py-4"><span aria-hidden="true" class="grid h-12 w-12 place-items-center rounded-full bg-green-50 text-xl font-bold text-green-700">✓</span><h2 id="step-heading" class="mt-5 text-2xl font-bold text-gray-900">این بخش‌ها ذخیره شدند</h2><p class="mt-3 max-w-lg text-sm leading-7 text-gray-600">هر زمان بخواهید می‌توانید برای تکمیل یا اصلاح اطلاعات غیرکلیدی برگردید. بخش‌های دیگر پرونده را از داشبورد دنبال کنید.</p><a wire:navigate href="{{ route('dashboard') }}" class="mt-7 inline-flex rounded-xl bg-green-700 px-5 py-3 text-sm font-semibold text-white hover:bg-green-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-700">بازگشت به داشبورد</a></div>
            @endif

            @if ($step < 4)
                <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 pt-6">
                    @if ($step > 1)<button type="button" wire:click="previous" class="rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-green-700">مرحله قبل</button>@else <span class="text-xs text-gray-500">* اطلاعات ضروری</span> @endif
                    <button type="button" wire:click="saveAndContinue" wire:loading.attr="disabled" wire:target="saveAndContinue" class="inline-flex items-center gap-2 rounded-xl bg-green-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-green-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-green-700 disabled:opacity-60"><span wire:loading.remove wire:target="saveAndContinue">ذخیره و ادامه</span><span wire:loading wire:target="saveAndContinue">در حال ذخیره...</span><span aria-hidden="true">←</span></button>
                </div>
            @endif
        </section>
    </div>
</div>
