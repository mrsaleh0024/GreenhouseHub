<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Livewire\Concerns\HasFarsiValidation;
use App\Models\User;
use App\Support\Mobile;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Super admin only: creates admin accounts and (de)activates them.
 * Livewire actions are callable directly, so every action re-checks the role.
 */
#[Layout('layouts.app')]
#[Title('مدیریت مدیران')]
class AdminManager extends Component
{
    use HasFarsiValidation;
    use WithPagination;

    public string $name = '';
    public string $mobile = '';

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
    }

    public function create(): void
    {
        $this->authorizeSuperAdmin();

        $this->mobile = Mobile::normalize($this->mobile);

        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'mobile' => ['required', 'regex:/^09\d{9}$/', Rule::unique('users', 'mobile')],
        ]);

        $user = new User(['name' => trim($this->name), 'mobile' => $this->mobile]);
        $user->status = UserStatus::Pending;
        $user->save();
        $user->assignRole(Role::Admin->value);

        $this->reset(['name', 'mobile']);
        $this->resetPage();

        session()->flash('created', 'حساب مدیر ساخته شد. از او بخواهید با همین شماره از صفحه‌ی فعال‌سازی، رمز عبور خود را تعیین کند.');
    }

    public function toggleStatus(int $id): void
    {
        $this->authorizeSuperAdmin();

        // Scoped to the admin role: super admins cannot be changed from here.
        $admin = User::role(Role::Admin->value)->findOrFail($id);

        if ($admin->status === UserStatus::Pending) {
            return;
        }

        if ($admin->status === UserStatus::Active) {
            $admin->status = UserStatus::Inactive;
            $admin->save();
            $admin->forgetSessions();

            return;
        }

        $admin->status = UserStatus::Active;
        $admin->save();
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole(Role::SuperAdmin->value), 403);
    }

    public function render()
    {
        return view('livewire.admin.admin-manager', [
            'admins' => User::role(Role::Admin->value)->latest()->paginate(15),
        ]);
    }
}
