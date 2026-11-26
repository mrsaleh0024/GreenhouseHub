export function normalize(value) {
    return value.normalize('NFKC').replace(/[يى]/g, 'ی').replace(/ك/g, 'ک')
        .replace(/[\u064b-\u065f\u0670]/g, '').replace(/[\s\u200c]/g, '').toLowerCase();
}

export function distance(a, b) {
    let previous = Array.from({ length: b.length + 1 }, (_, i) => i);
    for (let i = 1; i <= a.length; i++) {
        const row = [i];
        for (let j = 1; j <= b.length; j++) {
            row[j] = Math.min(row[j - 1] + 1, previous[j] + 1, previous[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
        }
        previous = row;
    }
    return previous[b.length];
}

export function matchesQuery(label, query) {
    const needle = normalize(query);
    if (!needle || normalize(label).includes(needle)) return true;
    if (needle.length < 3) return false;
    const tolerance = needle.length >= 6 ? 2 : 1;
    return [label, ...label.split(/\s+/)].some(word => distance(normalize(word), needle) <= tolerance);
}

export default () => ({
    query: '',
    expanded: false,
    revision: 0,
    init() {
        this.$watch('query', () => { this.expanded = false; });
    },
    matches(label, element) {
        this.revision;
        if (this.query.trim()) return matchesQuery(label, this.query);
        if (this.expanded || element.querySelector('input')?.checked) return true;
        return Array.from(this.$root.querySelectorAll('[data-catalog-option]')).indexOf(element) < 4;
    },
    get hasResults() {
        return Array.from(this.$root.querySelectorAll('[data-catalog-option]')).some(el => matchesQuery(el.textContent, this.query));
    },
});
