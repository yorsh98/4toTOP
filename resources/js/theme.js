// Tema compartido por sitio público y backoffice. La misma regla de lectura vive en
// resources/views/partials/theme-head.blade.php, que la aplica antes del primer pintado.
const KEY = 'fo-theme';
const root = document.documentElement;
const systemDark = () => {
    try { return matchMedia('(prefers-color-scheme: dark)'); } catch (_) { return null; }
};

// Elección de esta pestaña cuando localStorage no acepta escrituras
let unsaved = null;

const readTheme = () => {
    if (unsaved) return unsaved;
    try {
        const saved = localStorage.getItem(KEY);
        if (saved === 'dark' || saved === 'light') return saved;
    } catch (_) {}
    return systemDark()?.matches ? 'dark' : 'light';
};

export function syncThemeButtons() {
    const label = root.dataset.theme === 'light' ? 'Cambiar a tema oscuro' : 'Cambiar a tema claro';
    document.querySelectorAll('[data-fo-theme]').forEach((btn) => btn.setAttribute('aria-label', label));
}

const show = (theme) => {
    root.dataset.theme = theme;
    const store = window.Alpine?.store('theme');
    if (store) store.value = theme;
    syncThemeButtons();
};

const apply = () => show(readTheme());

export function toggleTheme() {
    const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try {
        localStorage.setItem(KEY, theme);
        unsaved = null;
    } catch (_) {
        unsaved = theme;
    }
    show(theme);
}

export function initTheme() {
    const register = () => {
        if (!window.Alpine || window.Alpine.store('theme')) return;
        window.Alpine.store('theme', { value: readTheme(), toggle: toggleTheme });
    };
    document.addEventListener('alpine:init', register);
    register();
    // Sin elección, readTheme devuelve la del sistema; con elección, este cambio no altera nada
    systemDark()?.addEventListener?.('change', apply);
    // Livewire reemplaza los atributos de <html> al navegar: hay que reponer el tema en el cambio
    document.addEventListener('livewire:navigating', (event) => event.detail?.onSwap?.(apply));
    document.addEventListener('livewire:navigated', apply);
}
