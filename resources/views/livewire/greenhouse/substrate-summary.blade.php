@if ($cycle->substrate && $cycle->substrate_details)
    @php($profile = \App\Services\SubstrateProfile::forItem($cycle->substrate))
    @php($fields = $cycle->substrate_details['fields'] ?? [])
    <details class="mt-5 rounded-xl bg-gray-50 p-4"><summary class="cursor-pointer text-sm font-semibold text-green-700">شرایط بستر: {{ $cycle->substrate->label_fa }}</summary>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
            @foreach ($profile['fields'] as $key => $field)
                @if (isset($fields[$key]))<div><dt class="text-xs text-gray-500">{{ $field['label'] }}</dt><dd class="mt-1 text-gray-800">{{ $field['options'][$fields[$key]] ?? 'ثبت نشده' }}</dd></div>@endif
            @endforeach
            @foreach (['volume_l' => 'حجم بستر یا مخزن (لیتر)', 'ph' => 'pH', 'ec_ms_cm' => 'EC (mS/cm)'] as $key => $label)
                @if (isset($fields[$key]) && $fields[$key] !== '')<div><dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="mt-1 text-gray-800">{{ $fields[$key] }}</dd></div>@endif
            @endforeach
            @if (!empty($fields['measurement_date']))<div><dt class="text-xs text-gray-500">تاریخ اندازه‌گیری</dt><dd class="mt-1">{{ \App\Support\JalaliDate::fromGregorian($fields['measurement_date']) }}</dd></div>@endif
            @if (!empty($fields['measurement_method']))<div><dt class="text-xs text-gray-500">روش اندازه‌گیری</dt><dd class="mt-1">{{ ['laboratory' => 'آزمایشگاه', 'direct' => 'مستقیم محلول', 'pour_through' => 'زه‌آب', 'extract_1_2' => 'عصاره ۱ به ۲', 'saturated_extract' => 'عصاره اشباع', 'unknown' => 'نامشخص'][$fields['measurement_method']] ?? 'نامشخص' }}</dd></div>@endif
        </dl>
        @if (!auth()->user()->hasRole('expert') && !empty($fields['composition']))<p class="mt-4 whitespace-pre-wrap text-sm leading-7 text-gray-700">{{ $fields['composition'] }}</p>@endif
    </details>
@endif
