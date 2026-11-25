<div>
    <header class="bg-white shadow">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-xl font-bold text-gray-800">مدیریت مدیران</h1>
        </div>
    </header>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('created'))
            <x-auth.alert>
                {{ session('created') }}
                <span class="mt-1 block font-medium" dir="ltr">{{ route('activate') }}</span>
            </x-auth.alert>
        @endif

        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold text-gray-800">تعریف مدیر جدید</h2>

            <form wire:submit="create" class="grid gap-4 sm:grid-cols-3 sm:items-start">
                <x-auth.input label="نام و نام خانوادگی" name="name" wire:model="name" />
                <x-auth.input label="شماره موبایل" name="mobile" type="tel" wire:model="mobile" ltr
                              inputmode="numeric" placeholder="09123456789" />
                <div class="sm:pt-6">
                    <x-auth.button target="create">
                        <span wire:loading.remove wire:target="create">ایجاد مدیر</span>
                        <span wire:loading wire:target="create">در حال ایجاد...</span>
                    </x-auth.button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-start text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium">نام</th>
                            <th class="px-4 py-3 text-start font-medium">موبایل</th>
                            <th class="px-4 py-3 text-start font-medium">وضعیت</th>
                            <th class="px-4 py-3 text-start font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($admins as $admin)
                            <tr wire:key="admin-{{ $admin->id }}">
                                <td class="px-4 py-3 text-gray-800">{{ $admin->name }}</td>
                                <td class="px-4 py-3 text-gray-600" dir="ltr">{{ $admin->mobile }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-green-100 text-green-800' => $admin->status === \App\Enums\UserStatus::Active,
                                        'bg-yellow-100 text-yellow-800' => $admin->status === \App\Enums\UserStatus::Pending,
                                        'bg-gray-200 text-gray-700' => $admin->status === \App\Enums\UserStatus::Inactive,
                                    ])>{{ $admin->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($admin->status !== \App\Enums\UserStatus::Pending)
                                        <button wire:click="toggleStatus({{ $admin->id }})"
                                                wire:confirm="وضعیت این مدیر تغییر کند؟"
                                                class="text-sm text-green-700 hover:underline">
                                            {{ $admin->status === \App\Enums\UserStatus::Active ? 'غیرفعال‌سازی' : 'فعال‌سازی مجدد' }}
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400">در انتظار فعال‌سازی توسط کاربر</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">هنوز مدیری تعریف نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($admins->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $admins->links() }}</div>
            @endif
        </section>
    </div>
</div>
