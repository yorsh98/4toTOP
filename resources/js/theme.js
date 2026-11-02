const readTheme = () => {
    try {
        const saved = localStorage.getItem('fo-theme');
        if (saved === 'dark' || saved === 'light') return saved;
    } catch (_) {}
    return document.body?.classList.contains('fo') ? 'dark' : 'light';
};

const apply = () => {
    document.documentElement.dataset.theme = readTheme();
};

export function initTheme() {
    const register = () => {
        if (!window.Alpine || window.Alpine.store('theme')) return;
        window.Alpine.store('theme', {
            value: readTheme(),
            toggle() {
                this.value = this.value === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem('fo-theme', this.value); } catch (_) {}
                document.documentElement.dataset.theme = this.value;
            },
        });
    };
    document.addEventListener('alpine:init', register);
    register();
    document.addEventListener('livewire:navigating', (event) => event.detail?.onSwap?.(apply));
    document.addEventListener('livewire:navigated', () => {
        apply();
        if (window.Alpine?.store('theme')) window.Alpine.store('theme').value = document.documentElement.dataset.theme;
    });
}
