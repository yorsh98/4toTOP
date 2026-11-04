import { prefersReducedMotion } from './chat-motion.js';

let pending;

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-cm-page]');
    if (!button || button.disabled || prefersReducedMotion()) return;

    const nav = button.closest('[data-cm-pager]');
    const component = nav?.closest('[wire\\:id]');
    const current = Number(nav?.querySelector('[aria-current="page"]')?.textContent.trim());
    const page = button.dataset.cmPage;
    const dir = page === 'prev' ? -1 : page === 'next' ? 1 : Math.sign(Number(page) - current);
    if (!component || !dir) return;

    let scope = nav.parentElement;
    while (scope && scope !== component.parentElement && !scope.querySelector('[data-cm-paged], table')) scope = scope.parentElement;
    const target = scope?.querySelector('[data-cm-paged]') ?? scope?.querySelector('table');
    if (!target) return;

    pending?.target.classList.remove('cm-page-out');
    const selector = target.hasAttribute('data-cm-paged') ? '[data-cm-paged]' : 'table';
    pending = { target, dir, component, selector, index: [...component.querySelectorAll(selector)].indexOf(target) };
    target.classList.add('cm-page-out');
}, true);

const bindPageMotion = () => {
    if (!window.Livewire || window.__pageMotionBound) return;
    window.__pageMotionBound = true;
    let arrived;
    window.Livewire.hook('commit', ({ component, succeed, fail }) => {
        if (!pending || component.el !== pending.component) return;
        const change = pending;
        pending = null;
        fail(() => change.target.classList.remove('cm-page-out'));
        succeed(() => { arrived = change; });
    });
    // Tras el morph y antes del siguiente pintado: así las filas nuevas no se ven un cuadro sin animar
    window.Livewire.hook('morphed', ({ component }) => {
        if (!arrived || component.el !== arrived.component) return;
        const change = arrived;
        arrived = null;
        change.target.classList.remove('cm-page-out');
        const target = component.el.querySelectorAll(change.selector)[change.index];
        if (!target || prefersReducedMotion()) return;
        target.classList.remove('cm-page-out');
        const groups = target.querySelectorAll(':scope > tbody.fo-table-group');
        const rows = groups.length ? groups : target.matches('table')
            ? target.querySelectorAll(':scope > tbody > tr') : target.children;
        [...rows].filter((row) => row.getClientRects().length).forEach((row, i) => row.style.setProperty('--cm-i', Math.min(i, 8)));
        target.dataset.cmDir = change.dir;
        target.classList.add('cm-page-in');
        setTimeout(() => {
            target.classList.remove('cm-page-in');
            delete target.dataset.cmDir;
            [...rows].forEach((row) => row.style.removeProperty('--cm-i'));
        }, 900);
    });
};

document.addEventListener('livewire:init', bindPageMotion);
bindPageMotion();
