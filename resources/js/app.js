import $ from 'jquery';
window.$ = window.jQuery = $;

import 'bootstrap';

import Swal from 'sweetalert2';
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
    confirmButtonColor: '#dc2626',
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
