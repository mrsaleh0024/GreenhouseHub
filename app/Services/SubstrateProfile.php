<?php

namespace App\Services;

use App\Models\CatalogItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubstrateProfile
{
    public static function forItem(?CatalogItem $item): ?array
    {
        if (! $item) {
            return null;
        }
        foreach (config('substrate_profiles') as $key => $profile) {
            if (in_array($item->slug, $profile['slugs'], true)) {
                return ['key' => $key] + $profile;
            }
        }

        $profile = ['key' => 'solid'] + config('substrate_profiles.solid');
        $specific = match ($item->slug) {
            'cocopeat', 'coco_perlite_mix' => ['conditioning' => ['label' => 'فرآوری کوکوپیت', 'options' => ['washed_buffered' => 'شسته و بافرشده', 'washed' => 'فقط شسته‌شده', 'raw' => 'خام', 'unknown' => 'اطلاع ندارم']]],
            'rockwool' => ['format' => ['label' => 'شکل پشم سنگ', 'options' => ['slab' => 'اسلب', 'block' => 'بلوک کشت', 'cube' => 'مکعب نشاء', 'loose' => 'الیاف فله', 'unknown' => 'اطلاع ندارم']]],
            'perlite' => ['grade' => ['label' => 'دانه‌بندی پرلیت', 'options' => ['fine' => 'ریز', 'medium' => 'متوسط', 'coarse' => 'درشت', 'mixed' => 'مخلوط', 'unknown' => 'اطلاع ندارم']]],
            'peat_moss' => ['peat_type' => ['label' => 'نوع پیت‌ماس', 'options' => ['white' => 'روشن (بلوند)', 'black' => 'تیره', 'mix' => 'ترکیبی', 'unknown' => 'اطلاع ندارم']]],
            default => [],
        };
        $profile['fields'] = $specific + $profile['fields'];

        return $profile;
    }

    public static function validate(?CatalogItem $item, array $data): ?array
    {
        $profile = self::forItem($item);
        if (! $profile) {
            return null;
        }
        $rules = [];
        foreach ($profile['fields'] as $key => $field) {
            $rules[$key] = ['required', 'string', Rule::in(array_keys($field['options']))];
        }
        $rules += [
            'volume_l' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'ph' => ['nullable', 'numeric', 'between:0,14'],
            'ec_ms_cm' => ['nullable', 'numeric', 'between:0,100'],
            'measurement_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'required_with:ph,ec_ms_cm'],
            'measurement_method' => ['nullable', 'string', Rule::in(['laboratory', 'direct', 'pour_through', 'extract_1_2', 'saturated_extract', 'unknown']), 'required_with:ph,ec_ms_cm'],
            'composition' => ['nullable', 'string', 'max:500'],
        ];
        $validated = Validator::make(['substrateDetails' => $data], ['substrateDetails' => ['array:'.implode(',', array_keys($rules))]] + collect($rules)->mapWithKeys(fn ($rule, $key) => ['substrateDetails.'.$key => $rule])->all(), [
            'required' => 'یکی از گزینه‌ها را انتخاب کنید؛ اگر مطمئن نیستید «اطلاع ندارم» را بزنید.',
            'required_with' => 'برای ثبت نتیجه اندازه‌گیری، تاریخ و روش آن را هم وارد کنید.',
            'in' => 'گزینه انتخاب‌شده معتبر نیست.', 'numeric' => 'مقدار باید عدد باشد.', 'between' => 'مقدار خارج از محدوده ثبت است.',
        ])->validate()['substrateDetails'];

        return ['version' => 1, 'profile' => $profile['key'], 'fields' => $validated];
    }
}
