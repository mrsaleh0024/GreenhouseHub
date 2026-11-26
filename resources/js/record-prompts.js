export default (count) => ({
    index: 0,
    count,
    paused: false,
    interacting: false,
    timer: null,
    startX: null,
    swiped: false,
    motion: null,
    motionListener: null,
    init() {
        this.motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        this.paused = this.motion.matches;
        this.motionListener = (event) => { if (event.matches) this.paused = true; };
        this.motion.addEventListener('change', this.motionListener);
        this.timer = setInterval(() => {
            if (!this.paused && !this.interacting && !document.hidden) this.go(1);
        }, 7500);
    },
    go(direction) { this.index = (this.index + direction + this.count) % this.count; },
    swipe(event) {
        if (this.startX === null) return;
        const delta = event.clientX - this.startX;
        if (Math.abs(delta) > 45) { this.go(delta > 0 ? 1 : -1); this.paused = true; this.swiped = true; }
        this.startX = null;
    },
    destroy() {
        clearInterval(this.timer);
        this.motion?.removeEventListener('change', this.motionListener);
    },
});
