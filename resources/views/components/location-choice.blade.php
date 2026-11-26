@props(['items', 'model', 'label', 'value' => '', 'disabled' => false])
<div wire:key="location-{{ $model }}-{{ $items->pluck('id')->implode('-') }}" x-data="{ open: false }" class="location-choice">
    <button type="button" x-on:click="open = !open" :aria-expanded="open" @disabled($disabled) class="location-trigger">
        <span><small>{{ $label }}</small><strong>{{ $items->firstWhere('id', (int) $value)?->label_fa ?? ($disabled ? 'ابتدا استان را انتخاب کنید' : 'انتخاب '.$label) }}</strong></span><span aria-hidden="true" :class="{ 'rotate-180': open }">⌄</span>
    </button>
    <div x-cloak x-show="open" x-collapse class="location-options"><fieldset><legend class="sr-only">{{ $label }}</legend>
        @forelse ($items as $item)<label wire:key="location-option-{{ $model }}-{{ $item->id }}" class="location-option"><input type="radio" name="{{ $model }}" wire:model.live="{{ $model }}" value="{{ $item->id }}" x-on:change="open = false"><span>{{ $item->label_fa }}</span></label>@empty<p class="p-4 text-sm">گزینه‌ای ثبت نشده است.</p>@endforelse
    </fieldset></div>
    @error($model)<p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
