<?php

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\OtpSendException;
use App\Livewire\Concerns\HasFarsiValidation;
use App\Models\CatalogItem;
use App\Models\User;
use App\Services\OtpService;
use App\Services\OwnerRegistrationService;
use App\Support\Mobile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('ثبت‌نام')]
class Register extends Component
{
    use HasFarsiValidation;

    public string $role = 'greenhouse_owner';

    // Common
    public string $name = '';

    public string $mobile = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Greenhouse owner
    public string $greenhouse_name = '';

    public string $province_id = '';

    public string $city = '';

    public function updatedProvinceId(): void
    {
        $this->city = '';
    }

    // Expert
    public string $specialty = '';

    public string $license_number = '';

    public string $experience_years = '';

    public function register(OtpService $otp): void
    {
        $role = Role::tryFrom($this->role);

        // Livewire public properties are client-editable: never trust $role.
        if (! $role || ! $role->isSelfRegistrable()) {
            $this->addError('role', 'نوع حساب انتخاب‌شده معتبر نیست.');

            return;
        }

        $ipKey = 'register:'.request()->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $this->addError('mobile', 'تعداد درخواست‌های ثبت‌نام بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.');

            return;
        }

        $this->mobile = Mobile::normalize($this->mobile);
        $this->validate($this->rulesFor($role));

        RateLimiter::hit($ipKey, 3600);

        $existing = User::withTrashed()->where('mobile', $this->mobile)->first();

        // A never-confirmed self-registration may be taken over by whoever now
        // proves ownership of the number. Anything else counts as "taken".
        if ($existing && ! $this->isReclaimable($existing)) {
            $this->addError('mobile', 'این شماره موبایل قبلاً ثبت شده است. اگر رمز خود را فراموش کرده‌اید، از «فراموشی رمز عبور» استفاده کنید.');

            return;
        }

        DB::transaction(function () use ($existing, $role) {
            $user = $existing ?? new User;
            $user->fill([
                'name' => trim($this->name),
                'mobile' => $this->mobile,
                'password' => $this->password,
            ]);

            if (! $user->exists) {
                $user->status = UserStatus::Pending;
            }

            $user->save();
            $user->syncRoles([$role->value]);

            if ($role === Role::GreenhouseOwner) {
                $user->expertProfile()->delete();
                $city = CatalogItem::where('type', 'city')->where('status', 'approved')->where('parent_id', $this->province_id)->findOrFail($this->city);
                app(OwnerRegistrationService::class)->register($user, ['greenhouse_name' => trim($this->greenhouse_name), 'province_id' => (int) $this->province_id, 'city_id' => $city->id]);
            } else {
                $user->expertProfile()->updateOrCreate([], [
                    'specialty' => trim($this->specialty),
                    'license_number' => trim($this->license_number) ?: null,
                    'experience_years' => (int) $this->experience_years,
                ]);
            }
        });

        session()->put('pending_mobile', $this->mobile);

        try {
            $otp->send($this->mobile, OtpPurpose::Registration);
        } catch (OtpSendException $e) {
            session()->flash('otp_error', $e->getMessage());
        }

        $this->redirect(route('verify-mobile'), navigate: false);
    }

    private function isReclaimable(User $user): bool
    {
        return ! $user->trashed()
            && ! $user->isMobileVerified()
            && $user->hasPassword()
            && $user->status === UserStatus::Pending
            && $user->hasAnyRole(array_map(fn (Role $r) => $r->value, Role::selfRegistrable()));
    }

    /** @return array<string, mixed> */
    private function rulesFor(Role $role): array
    {
        $common = [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'password' => $this->passwordRules(),
        ];

        $specific = $role === Role::GreenhouseOwner
            ? [
                'greenhouse_name' => ['required', 'string', 'max:120'],
                'province_id' => ['required', 'integer', Rule::exists('catalog_items', 'id')->where('type', 'province')->where('status', 'approved')],
                'city' => ['required', 'integer', Rule::exists('catalog_items', 'id')->where('type', 'city')->where('status', 'approved')->where('parent_id', $this->province_id)],
            ]
            : [
                'specialty' => ['required', 'string', 'max:120'],
                'license_number' => ['nullable', 'string', 'max:50'],
                'experience_years' => ['required', 'integer', 'between:0,60'],
            ];

        return $common + $specific;
    }

    public function render()
    {
        // The registration card is wider than the other guest screens.
        return view('livewire.auth.register', [
            'cities' => CatalogItem::where('type', 'city')->where('status', 'approved')->where('parent_id', $this->province_id ?: null)->orderBy('label_fa')->get(),
            'provinces' => CatalogItem::where('type', 'province')->where('status', 'approved')->orderBy('sort_order')->get(),
        ])->layout('layouts.guest', ['maxWidth' => 'max-w-2xl']);
    }
}
