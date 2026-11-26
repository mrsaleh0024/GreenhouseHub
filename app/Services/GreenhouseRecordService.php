<?php

namespace App\Services;

use App\Models\Greenhouse;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GreenhouseRecordService
{
    public function saveBasics(Greenhouse $greenhouse, User $user, array $data): void
    {
        Gate::forUser($user)->authorize('update', $greenhouse);

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'area_m2' => ['nullable', 'numeric', 'gt:0', 'max:99999999.99'],
            'address' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'altitude_m' => ['nullable', 'integer', 'between:-32768,32767'],
            'commissioned_on' => ['nullable', 'date_format:Y-m-d'],
        ];

        $this->saveValidated($greenhouse, $data, $rules);
    }

    public function saveStructure(Greenhouse $greenhouse, User $user, array $data): void
    {
        Gate::forUser($user)->authorize('update', $greenhouse);

        $rules = [];
        foreach (['structure_type_id' => 'structure_type', 'cultivation_type_id' => 'cultivation_type'] as $column => $type) {
            $rules[$column] = ['nullable', 'integer', Rule::exists('catalog_items', 'id')->where(function ($query) use ($type, $user) {
                $query->where('type', $type)->where(function ($query) use ($user) {
                    $query->where('status', 'approved')->orWhere(function ($query) use ($user) {
                        $query->where('status', 'pending')->where('created_by', $user->id);
                    });
                });
            })];
        }

        $this->saveValidated($greenhouse, $data, $rules);
    }

    private function saveValidated(Greenhouse $greenhouse, array $data, array $rules): void
    {
        if (array_diff(array_keys($data), array_keys($rules)) !== []) {
            throw ValidationException::withMessages(['record' => 'تغییر اطلاعات کلیدی در این بخش مجاز نیست.']);
        }

        $values = Validator::make($data, $rules, [
            'required' => 'تکمیل این بخش الزامی است.',
            'required_with' => 'عرض و طول جغرافیایی باید با هم ثبت شوند.',
            'numeric' => 'مقدار واردشده باید عدد باشد.',
            'integer' => 'مقدار واردشده باید عدد صحیح باشد.',
            'gt' => 'مقدار واردشده باید بزرگ‌تر از صفر باشد.',
            'between' => 'مقدار واردشده خارج از محدوده مجاز است.',
            'max' => 'مقدار واردشده بیش از حد مجاز است.',
            'exists' => 'گزینه انتخاب‌شده معتبر نیست.',
            'date_format' => 'تاریخ واردشده معتبر نیست.',
        ])->validate();

        $greenhouse->fill($values)->save();
    }
}
