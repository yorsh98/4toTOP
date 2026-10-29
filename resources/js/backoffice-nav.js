// Barra lateral del backoffice (livewire/layout/navigation).
//
// Píldora de sección (cm-pill de chat-motion): marca el módulo activo y viaja de un ítem
// al siguiente al navegar. Se mueve en cuanto se hace clic; como wire:navigate reemplaza
// la barra entera, justo antes del cambio se guarda dónde iba y la píldora nueva sigue
// desde ahí hasta el ítem activo. La barra no repite ninguna animación al navegar.
//
// Colapsar / expandir con FLIP: el estado nuevo se aplica una sola vez (un cálculo de
// layout) y luego solo se anima transform y clip-path. Al colapsar, un panel fantasma
// pone el fondo mientras se recoge, así la barra real ya tiene su ancho final.
import { prefersReducedMotion } from './lib/chat-motion.js';

const EASE = 'cubic-bezier(.22, 1, .36, 1)'; // --cm-ease-out
const SPRING = 'cubic-bezier(.34, 1.56, .64, 1)'; // --cm-ease-spring
const DURATION = 320;
const LABELS = '[x-show="!collapsed"]';
const ICONS = '.bo-nav-link > i:first-child, .bo-nav-head .bo-nav-toggle';
const TRACK = '[data-bo-nav-track]';

const shown = (el) => el.getClientRects().length > 0;
const settle = (anims) => Promise.all(anims.map((a) => a.finished)).catch(() => {});

/* ---------- Píldora de sección ---------- */
const pillParts = () => {
    const track = document.querySelector(TRACK);
    const pill = track?.querySelector('.bo-nav-pill');
    return { track, pill, active: track?.querySelector('.bo-nav-link.is-active') };
};

function placePill(pill, link, { instant = false } = {}) {
    if (!pill) return;
    if (instant) pill.classList.add('is-instant');
    if (link) {
        pill.style.transform = `translateY(${link.offsetTop}px)`;
        pill.style.height = `${link.offsetHeight}px`;
        pill.style.setProperty('--bo-accent', link.style.getPropertyValue('--bo-accent'));
    }
    pill.classList.toggle('is-on', Boolean(link));
    if (instant) {
        void pill.offsetWidth;
        pill.classList.remove('is-instant');
    }
}

function syncPill() {
    const { track, pill, active } = pillParts();
    if (!pill) return;
    track.classList.add('has-pill');
    placePill(pill, active, { instant: true });
}

function initPill() {
    let from = null; // dónde iba la píldora justo antes de reemplazar la barra

    // Clic: la píldora sale de inmediato hacia el ítem elegido
    document.addEventListener('click', (e) => {
        const link = e.target.closest?.(`${TRACK} .bo-nav-link`);
        if (!link || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const { pill } = pillParts();
        if (pill) placePill(pill, link, { instant: prefersReducedMotion() });
    });

    document.addEventListener('livewire:navigating', (e) => {
        const { pill } = pillParts();
        if (pill?.classList.contains('is-on')) {
            const cs = getComputedStyle(pill);
            from = { transform: cs.transform, height: cs.height, accent: pill.style.getPropertyValue('--bo-accent') };
        }
        // Recién puesta la barra nueva (antes de pintarla): la píldora arranca donde iba la vieja
        e.detail?.onSwap?.(() => {
            const { track, pill: next } = pillParts();
            if (!next || !from) return;
            track.classList.add('has-pill');
            next.classList.add('is-instant', 'is-on');
            Object.assign(next.style, { transform: from.transform, height: from.height });
            next.style.setProperty('--bo-accent', from.accent);
        });
    });

    // Con Alpine ya aplicado en la barra nueva, termina el viaje hasta el ítem activo
    document.addEventListener('livewire:navigated', () => {
        const { track, pill, active } = pillParts();
        if (!pill) return;
        track.classList.add('has-pill');
        if (!from || prefersReducedMotion()) {
            placePill(pill, active, { instant: true });
        } else {
            void pill.offsetWidth;
            pill.classList.remove('is-instant');
            placePill(pill, active);
        }
        from = null;
    });

    addEventListener('resize', syncPill);
    syncPill();
}

/* ---------- Colapsar / expandir ---------- */
// apply(): cambia el estado de Alpine y espera su $nextTick
async function toggle(nav, apply) {
    const aside = nav.querySelector('.bo-sidebar');
    const content = nav.nextElementSibling;
    if (prefersReducedMotion() || !aside || !content || !shown(aside)) {
        await apply();
        syncPill();
        return;
    }
    if (nav.dataset.boAnimating) return;
    nav.dataset.boAnimating = '1';

    // Al colapsar, los textos se van antes de que el panel se recoja
    const leaving = [...aside.querySelectorAll(LABELS)].filter(shown);
    const fades = leaving.map((el) => el.animate(
        [{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateX(-6px)' }],
        { duration: 120, easing: 'ease-in', fill: 'forwards' },
    ));
    await settle(fades);

    const icons = [...aside.querySelectorAll(ICONS)];
    const iconX = icons.map((i) => i.getBoundingClientRect().left);
    const before = nav.getBoundingClientRect().width;

    await apply();
    fades.forEach((a) => a.cancel());
    syncPill();

    // Todo se mide con la barra ya en su ancho final
    const after = nav.getBoundingClientRect().width;
    const opts = { duration: DURATION, easing: EASE };
    const anims = [
        content.animate([{ transform: `translateX(${before - after}px)` }, { transform: 'none' }], opts),
    ];
    icons.forEach((icon, n) => {
        const dx = iconX[n] - icon.getBoundingClientRect().left;
        if (dx) anims.push(icon.animate([{ transform: `translateX(${dx}px)` }, { transform: 'none' }], opts));
    });

    let ghost = null;
    if (after > before) {
        // Expandir: la barra nueva se descubre desde el ancho anterior
        const clip = (w) => `inset(0px ${after - w}px 0px 0px)`;
        anims.push(aside.animate([{ clipPath: clip(before) }, { clipPath: clip(after) }], opts));
        [...aside.querySelectorAll(LABELS)].filter(shown).forEach((el, n) => el.animate(
            [{ opacity: 0, transform: 'translateX(-8px)' }, { opacity: 1, transform: 'none' }],
            { duration: 240, delay: 60 + n * 14, easing: EASE, fill: 'backwards' },
        ));
    } else {
        // Colapsar: el fondo que sobra se recoge detrás de la barra ya angosta
        ghost = document.createElement('div');
        ghost.className = 'bo-nav-ghost';
        ghost.style.width = `${before}px`;
        nav.insertBefore(ghost, aside);
        const clip = (w) => `inset(0px ${before - w}px 0px 0px)`;
        anims.push(ghost.animate([{ clipPath: clip(before) }, { clipPath: clip(after) }], opts));
    }

    const arrow = nav.querySelector('.bo-nav-head .bo-nav-toggle i');
    arrow?.animate([{ transform: 'rotate(180deg) scale(.6)' }, { transform: 'none' }], { duration: 420, easing: SPRING });

    await settle(anims);
    ghost?.remove();
    delete nav.dataset.boAnimating;
}

// Ícono que gira y rebota al cambiar (tema, menú móvil)
function spin(icon) {
    if (!icon || prefersReducedMotion()) return;
    icon.animate([{ transform: 'rotate(-120deg) scale(.4)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 420, easing: SPRING });
}

export function initBackofficeNav() {
    window.boSidebar = { toggle, spin };
    initPill();
}
