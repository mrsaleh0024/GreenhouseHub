// Livewire 3 ships Alpine and starts it for us, so app.js must NOT start a
// second copy. Instead, register Alpine components/plugins on the instance
// Livewire exposes, inside the `alpine:init` hook.
//
// Plugins follow the same pattern:
// import focus from '@alpinejs/focus';
// document.addEventListener('alpine:init', () => window.Alpine.plugin(focus));

document.addEventListener('alpine:init', () => {
    window.Alpine.data('temperatureZone', () => ({
        open: true,
        temp: 22,

        get status() {
            if (this.temp < 18) {
                return { label: 'Too cold — vents closed.', tone: 'cold' };
            }

            if (this.temp > 26) {
                return { label: 'Too warm — open the vents.', tone: 'warm' };
            }

            return { label: 'Optimal range.', tone: 'ok' };
        },

        get badgeClass() {
            return {
                cold: 'bg-sky-400/10 text-sky-300',
                warm: 'bg-amber-400/10 text-amber-300',
                ok: 'bg-emerald-400/10 text-emerald-300',
            }[this.status.tone];
        },
    }));
});
