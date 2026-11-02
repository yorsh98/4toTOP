import $ from 'jquery';
window.$ = window.jQuery = $;

import Swal from 'sweetalert2';
import { initFrontOffice } from './front-office.js';
import { initBackofficeNav } from './backoffice-nav.js';
import { initTheme } from './theme.js';
window.Swal = Swal;

window.SwalToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

window.confirmDestructiveAction = (title, text, confirmButtonText = 'Sí, eliminar') => Swal.fire({
    icon: 'warning',
    title,
    text,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancelar',
    confirmButtonColor: getComputedStyle(document.body).getPropertyValue('--bo-danger').trim() || '#dc2626',
});

const bindLivewireToastListeners = () => {
    if (window.__livewireToastListenersBound || !window.Livewire) {
        return;
    }

    window.__livewireToastListenersBound = true;

    window.Livewire.on('app-toast', (event) => {
        const toast = event?.detail ?? event ?? {};

        window.SwalToast.fire({
            icon: toast.icon ?? 'success',
            title: toast.message ?? 'Operación realizada correctamente.',
        });
    });
};

const bindLivewireErrorPages = () => {
    if (window.__livewireErrorPagesBound || !window.Livewire) {
        return;
    }

    window.__livewireErrorPagesBound = true;

    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            if (![401, 403, 404, 419, 429, 500, 503].includes(status)
                || typeof content !== 'string'
                || !content.includes('Registro de navegación')) {
                return;
            }

            preventDefault();
            document.open();
            document.write(content);
            document.close();
        });
    });
};

document.addEventListener('livewire:init', bindLivewireToastListeners);
document.addEventListener('livewire:init', bindLivewireErrorPages);
bindLivewireToastListeners();
bindLivewireErrorPages();

const showFlashNotifications = (root = document) => {
    const notifications = [
        ...(root.matches?.('[data-flash-notification]') ? [root] : []),
        ...root.querySelectorAll('[data-flash-notification]'),
    ];

    notifications.forEach((notification) => {
        window.SwalToast.fire({
            icon: notification.dataset.icon,
            title: notification.dataset.message,
        });

        notification.remove();
    });
};

showFlashNotifications();

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                showFlashNotifications(node);
            }
        });
    });
}).observe(document.body, { childList: true, subtree: true });

// "Organizar PDF" (/convertir-archivos): thumbnails drawn with pdf.js, loaded only when that tool is used.
window.pdfOrganizer = (maxPages) => ({
    pages: [],
    loading: false,
    error: null,
    dragged: null,

    async load(file) {
        this.pages = [];
        this.error = null;
        this.sync();
        if (!file) {
            return;
        }

        this.loading = true;
        try {
            const { renderThumbnails } = await import('./pdf-thumbnails');
            await renderThumbnails(file, maxPages, ({ page, src }) => this.pages.push({ page, src, rotation: 0 }));
        } catch (error) {
            console.error(error);
            this.pages = [];
            this.error = error.message?.startsWith('Organizar') ? error.message : 'No se pudo leer el PDF para mostrar sus páginas.';
        } finally {
            this.loading = false;
            this.sync();
        }
    },

    rotate(index) {
        this.pages[index].rotation = (this.pages[index].rotation + 90) % 360;
        this.sync();
    },

    remove(index) {
        this.pages.splice(index, 1);
        this.sync();
    },

    move(from, to) {
        if (to < 0 || to >= this.pages.length || from === to) {
            return;
        }
        this.pages.splice(to, 0, this.pages.splice(from, 1)[0]);
        this.sync();
    },

    drop(index) {
        if (this.dragged !== null) {
            this.move(this.dragged, index);
        }
        this.dragged = null;
    },

    // Deferred: the plan travels with the next request (the submit), not on every change.
    sync() {
        this.$wire.set('options.plan', JSON.stringify(this.pages.map(({ page, rotation }) => ({ page, rotation }))), false);
    },
});

// "Cortar audio": the dialog and its four steps live here so it opens at once; the editor (audio-editor.js) is
// merged in once its chunk loads and overrides the placeholders below (clock, plan, load, loaded…).
window.audioCutter = (maxRegions, maxBytes) => ({
    ready: false, error: null, pendingFile: null, pendingPeaks: null, disposed: false,
    isOpen: false, step: 1, dir: 1, submitted: false, submitting: false, over: false, tooLarge: false, progress: null,
    titles: { 1: 'Sube el audio', 2: 'Marca los fragmentos', 3: 'Elige qué hacer', 4: 'Corta y descarga' },
    file: null, duration: 0, hasEditor: false, regions: [],
    load(file) { this.pendingFile = file; this.file = file ?? null; },
    loaded(event) { this.pendingPeaks = event; },
    clock() { return ''; },
    plan() { return { segments: [], final: 0, files: 0 }; },
    destroy() { this.disposed = true; },
    async init() {
        this.$nextTick(() => this.open());
        try {
            const { createCutter } = await import('./audio-editor');
            if (this.disposed) return;
            const file = this.pendingFile;
            const peaks = this.pendingPeaks;
            Object.assign(this, createCutter(maxRegions));
            this.pendingFile = this.pendingPeaks = null;
            this.ready = true;
            if (file) this.load(file);
            if (peaks) this.loaded(peaks);
        } catch (error) {
            console.error(error);
            this.error = 'No se pudo cargar el editor de audio. Recarga la página e inténtalo de nuevo.';
        }
    },
    open() {
        const dialog = this.dialogEl();
        // showModal() focuses the [autofocus] element: the current step's heading, not the close button.
        [1, 2, 3, 4].forEach(n => this.heading(n)?.toggleAttribute('autofocus', n === this.step));
        if (!dialog.open) dialog.showModal();
        requestAnimationFrame(() => { this.isOpen = true; });
        this.focusStep();
    },
    // Closing keeps the work: reopening continues where the user left off.
    close() {
        this.isOpen = false;
        this.stop?.();
        setTimeout(() => { if (!this.isOpen) this.dialogEl().close(); }, 180);
    },
    // Looked up by id, not through $refs or $root: right after Livewire's re-render those magics come back empty
    // (the x-ref entries of replaced nodes are dropped and restored later), and focus has to move at that moment.
    // The dialog is rendered once per page, so its ids are unique.
    dialogEl() {
        return document.getElementById('audio-cutter');
    },
    heading(n) {
        return document.getElementById('ac-h' + n);
    },
    canGo(n) {
        return n <= 1 || (n === 2 ? this.hasEditor : this.hasEditor && this.regions.length > 0);
    },
    whyLocked(n) {
        return n === 2 ? 'Sube un audio para continuar.' : 'Marca al menos un fragmento para continuar.';
    },
    go(n, force = false) {
        if (n < 1 || n > 4 || n === this.step || (!force && !this.canGo(n))) return;
        this.dir = n > this.step ? 1 : -1;
        this.step = n;
        this.focusStep();
    },
    // The incoming step is shown by its transition a few frames later; focusing it earlier would silently fail.
    focusStep(frames = 30) {
        requestAnimationFrame(() => {
            const heading = this.heading(this.step);
            if (heading?.getClientRects().length) heading.focus({ preventScroll: true });
            else if (frames > 0) this.focusStep(frames - 1);
        });
    },
    stepState(n) {
        if (n === this.step) return 'active';
        if (n < this.step) return 'done';
        return this.canGo(n) ? 'open' : 'locked';
    },
    summary(n) {
        const count = this.regions.length;
        return {
            1: this.file ? this.file.name + (this.duration ? ' · ' + this.clock(this.duration) : '') : 'Elige un archivo',
            2: count ? count + (count === 1 ? ' fragmento' : ' fragmentos') : 'Sin fragmentos',
            3: this.modeLabel(),
            4: this.submitted ? 'Enviado' : 'Revisa y corta',
        }[n];
    },
    modeLabel(mode = this.$wire.options.mode) {
        return { keep: 'Quedarme solo con lo marcado', remove: 'Quitar lo marcado', silence: 'Silenciar lo marcado' }[mode] ?? '';
    },
    finalText(mode = this.$wire.options.mode) {
        const plan = this.plan(mode);
        if (!plan.segments.length) return '—';
        return plan.files > 1 ? plan.files + ' archivos · ' + this.clock(plan.final) + ' en total' : this.clock(plan.final);
    },
    actionText() {
        const options = this.$wire.options;
        const count = this.regions.length;
        const parts = [this.modeLabel() + ' (' + count + (count === 1 ? ' fragmento)' : ' fragmentos)')];
        if (+options.fade_ms && options.mode !== 'silence') parts.push('fundido de ' + String(options.fade_ms / 1000).replace('.', ',') + ' s');
        if (options.normalize) parts.push('volumen normalizado');
        return parts.join(' · ');
    },
    resultName() {
        const base = (this.file?.name ?? 'audio').replace(/\.[^.]+$/, '') + '_recortado.';
        return base + (this.plan().files > 1 ? 'zip' : this.$wire.options.output);
    },
    // Runs in the capture phase on the drop zone, so an oversized file never starts an upload the server would drop.
    checkSize(event) {
        const file = event.target.files?.[0];
        this.tooLarge = !!file && file.size > maxBytes;
        if (this.tooLarge) {
            event.stopPropagation();
            event.target.value = '';
        }
    },
    drop(event) {
        this.over = false;
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.$refs.file.files = transfer.files;
        this.$refs.file.dispatchEvent(new Event('change', { bubbles: true }));
    },
    // Both buttons disappear with the server's re-render, so the step changes (and its focus) wait for it.
    async startOver() {
        await this.$wire.startOver();
        this.load(null);
        this.submitted = false;
        this.go(1, true);
    },
    async editAgain(step) {
        await this.$wire.editAgain();
        this.submitted = false;
        this.go(step, true);
    },
    // A failed validation sends the user to the step that holds the message.
    async submit() {
        if (this.submitting) return;
        this.submitting = true;
        try {
            await this.$wire.convert();
        } finally {
            this.submitting = false;
        }
        await this.$nextTick();
        const error = this.dialogEl().querySelector('[data-ac-error]');
        if (!error) return;
        error.closest('details')?.setAttribute('open', '');
        this.go(+error.dataset.acError, true);
    },
});

initTheme();
initFrontOffice();
initBackofficeNav();
