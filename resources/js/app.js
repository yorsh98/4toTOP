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
