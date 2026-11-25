@props(['seconds' => 0])

<div
    x-data="{
        left: {{ (int) $seconds }},
        timer: null,
        start(n) {
            this.left = n;
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                if (this.left > 0) { this.left--; } else { clearInterval(this.timer); }
            }, 1000);
        },
    }"
    x-init="start(left)"
    x-on:otp-sent.window="start($event.detail.seconds)"
    class="text-center text-sm"
>
    <button
        type="button"
        wire:click="resend"
        x-bind:disabled="left > 0"
        class="font-medium text-green-700 hover:underline disabled:cursor-not-allowed disabled:text-gray-400 disabled:no-underline"
    >
        <span x-show="left > 0" x-cloak>ارسال مجدد کد تا <span x-text="left"></span> ثانیه دیگر</span>
        <span x-show="left <= 0">ارسال مجدد کد</span>
    </button>
</div>
