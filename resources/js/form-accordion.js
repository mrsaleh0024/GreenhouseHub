export default () => ({
    animations: new Map(),
    reveal(panel) {
        this.$root.querySelectorAll('details[open]').forEach(other => {
            if (other !== panel && !other.contains(panel) && !panel.contains(other)) this.animate(other, false);
        });
        if (!panel.open || panel.dataset.closing === 'true') this.animate(panel, true);
    },
    toggle(event) {
        const summary = event.target.closest('summary');
        if (!summary || !this.$root.contains(summary)) return;
        event.preventDefault();
        const panel = summary.parentElement;
        const opening = panel.dataset.closing === 'true' || !panel.open;
        if (opening) {
            this.$root.querySelectorAll('details[open]').forEach(other => {
                if (other !== panel && !other.contains(panel) && !panel.contains(other)) this.animate(other, false);
            });
        }
        this.animate(panel, opening);
    },
    animate(panel, opening) {
        const start = panel.getBoundingClientRect().height;
        this.animations.get(panel)?.cancel();
        panel.dataset.closing = String(!opening);
        panel.open = true;
        const style = getComputedStyle(panel);
        const end = opening ? panel.scrollHeight + parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth)
            : panel.querySelector('summary').getBoundingClientRect().height + parseFloat(style.paddingTop) + parseFloat(style.paddingBottom) + parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
        panel.style.overflow = 'hidden';
        const motion = panel.animate([{ height: `${start}px` }, { height: `${end}px` }], {
            duration: matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 280,
            easing: 'cubic-bezier(.22,1,.36,1)',
        });
        this.animations.set(panel, motion);
        motion.onfinish = () => {
            panel.open = opening;
            panel.style.overflow = '';
            delete panel.dataset.closing;
            this.animations.delete(panel);
        };
    },
    destroy() { this.animations.forEach(animation => animation.cancel()); },
});
