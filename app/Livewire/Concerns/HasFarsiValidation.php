<?php

namespace App\Livewire\Concerns;

/**
 * Persian validation messages/attribute names, picked up automatically by
 * Livewire's validate().
 */
trait HasFarsiValidation
{
    protected function messages(): array
    {
        return [
            'required' => ':attribute الزامی است.',
            'string' => ':attribute معتبر نیست.',
            'min' => ':attribute باید حداقل :min کاراکتر باشد.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'regex' => ':attribute معتبر نیست.',
            'confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'in' => ':attribute انتخاب‌شده معتبر نیست.',
            'integer' => ':attribute باید عدد صحیح باشد.',
            'between' => ':attribute باید بین :min تا :max باشد.',
            'digits' => 'کد تأیید باید :digits رقم باشد.',
            'mobile.regex' => 'شماره موبایل معتبر نیست (مثال: 09123456789).',
            'mobile.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.regex' => 'رمز عبور باید حداقل شامل یک حرف و یک عدد باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'role' => 'نوع حساب',
            'name' => 'نام و نام خانوادگی',
            'mobile' => 'شماره موبایل',
            'password' => 'رمز عبور',
            'code' => 'کد تأیید',
            'greenhouse_name' => 'نام گلخانه',
            'province' => 'استان',
            'city' => 'شهر',
            'specialty' => 'تخصص',
            'license_number' => 'شماره نظام/مجوز',
            'experience_years' => 'سابقه‌ی کار',
        ];
    }

    /** @return list<string> */
    protected function passwordRules(): array
    {
        return [
            'required', 'string', 'min:8', 'max:64', 'confirmed',
            'regex:/^(?=.*\p{L})(?=.*[0-9۰-۹]).+$/u',
        ];
    }
}
