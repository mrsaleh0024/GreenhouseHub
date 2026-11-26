@props(['greenhouse', 'mode' => 'owner'])

<section class="zone-overview zone-overview--{{ $mode }}" aria-label="بخش‌های {{ $greenhouse->name }}">
    <div class="zone-overview-heading">
        <div class="flex items-center gap-3">
            <span class="zone-overview-icon"><x-line-icon name="layers" /></span>
            <div><h3>{{ $mode === 'expert' ? 'ساختار فنی گلخانه' : 'بخش‌های گلخانه' }}</h3><p>{{ $greenhouse->zones_count }} بخش ثبت‌شده · سالن‌ها و فضاهای مستقل کشت</p></div>
        </div>
        <a wire:navigate href="{{ route('greenhouses.zones', $greenhouse) }}" class="zone-overview-link">{{ $mode === 'expert' ? 'بررسی تجهیزات' : ($mode === 'manager' ? 'مدیریت بخش‌ها' : 'مدیریت و افزودن') }} <x-line-icon name="arrow-left" /></a>
    </div>
    <div class="zone-overview-grid">
        @forelse ($greenhouse->zones as $zone)
            <a wire:navigate href="{{ route('greenhouses.zones', $greenhouse) }}#zone-{{ $zone->id }}" class="zone-tile">
                <div class="zone-tile-top"><x-line-icon name="greenhouse" /><span>{{ $zone->status === 'active' ? 'فعال' : 'غیرفعال' }}</span></div>
                <strong>{{ $zone->name }}</strong>
                <dl><div><dt>مساحت</dt><dd>{{ $zone->area_m2 ? number_format((float) $zone->area_m2).' متر مربع' : 'ثبت نشده' }}</dd></div><div><dt>ظرفیت</dt><dd>{{ $zone->capacity_plants ? number_format($zone->capacity_plants).' بوته' : 'ثبت نشده' }}</dd></div></dl>
                <span class="zone-tile-action">{{ $mode === 'expert' ? 'مشاهده جزئیات' : 'جزئیات و ویرایش' }} <x-line-icon name="arrow-left" /></span>
            </a>
        @empty
            <div class="zone-overview-empty"><x-line-icon name="greenhouse" /><p>هنوز بخشی ثبت نشده است.<br><span>{{ $mode === 'expert' ? 'اطلاعات سالن‌ها پس از ثبت در اینجا دیده می‌شود.' : 'با معرفی سالن‌ها، ظرفیت و تجهیزات هر قسمت را جداگانه دنبال کنید.' }}</span></p></div>
        @endforelse
    </div>
    @if ($greenhouse->zones_count > 6)<a wire:navigate href="{{ route('greenhouses.zones', $greenhouse) }}" class="zone-overview-link mt-4">مشاهده همه {{ $greenhouse->zones_count }} بخش <x-line-icon name="arrow-left" /></a>@endif
</section>
