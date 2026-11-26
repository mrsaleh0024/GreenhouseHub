<div x-data="{ stage: 0, holding: false, timer: null, start() { if (this.holding) return; this.holding = true; this.timer = setTimeout(() => { this.holding = false; this.$wire.remove(); }, 3100); }, stop() { clearTimeout(this.timer); this.holding = false; } }" x-on:keydown.escape.window="stop(); stage = 0">
    <button type="button" x-on:click="stage = 1" class="mt-5 text-sm font-semibold text-red-700">حذف گلخانه</button>
    <div x-cloak x-show="stage > 0" class="fixed inset-0 z-50 grid place-items-center bg-gray-900/40 p-5 backdrop-blur-sm" x-on:click.self="stop(); stage = 0">
        <section role="dialog" aria-modal="true" aria-label="تأیید حذف گلخانه" x-trap.inert.noscroll="stage > 0" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h2 class="text-xl font-bold">حذف گلخانه و اطلاعات وابسته</h2>
            <p class="mt-4 text-sm leading-7">همه بخش‌ها، تجهیزات و سوابق کشت این گلخانه از پنل شما حذف می‌شوند. بازیابی پرونده و اطلاعات آن فقط توسط مدیر امکان‌پذیر است.</p>
            <button type="button" x-show="stage === 1" x-on:click="await $wire.confirmFirst(); stage = 2" class="mt-5 rounded-xl bg-red-700 px-5 py-3 text-sm font-semibold text-white">متوجه شدم، ادامه حذف</button>
            <div x-show="stage === 2"><p class="mt-4 text-sm leading-7">برای تأیید نهایی، دکمه را ۳ ثانیه نگه دارید. رهاکردن زودتر، تأیید را لغو می‌کند.</p>
                <button type="button" class="water-confirm mt-4" :class="{ 'is-holding': holding }" x-on:pointerdown.prevent="if ($event.button === 0) start()" x-on:pointerup.window="stop()" x-on:pointercancel.window="stop()" x-on:pointerleave="stop()" x-on:blur="stop()" x-on:keydown.space.prevent="if (!$event.repeat) start()" x-on:keydown.enter.prevent="if (!$event.repeat) start()" x-on:keyup.space.prevent="stop()" x-on:keyup.enter.prevent="stop()" x-on:contextmenu.prevent wire:loading.attr="disabled"><span class="water-fill" aria-hidden="true"></span><span class="relative">نگه دارید تا گلخانه حذف شود</span></button>
            </div>
            <button type="button" x-on:click="stop(); stage = 0" class="mt-5 block text-sm font-semibold text-gray-700">انصراف و بازگشت</button>
        </section>
    </div>
</div>
