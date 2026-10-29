// Barra lateral del backoffice (livewire/layout/navigation): colapsar / expandir con FLIP.
// El estado nuevo se aplica una sola vez (un cálculo de layout) y después solo se anima
// transform y clip-path: el panel se recorta al ancho nuevo mientras el contenido se
// desliza en sincronía y los íconos viajan a su sitio. Curvas de chat-motion.
import { prefersReducedMotion } from './lib/chat-motion.js';

const EASE = 'cubic-bezier(.22, 1, .36, 1)'; // --cm-ease-out
const SPRING = 'cubic-bezier(.34, 1.56, .64, 1)'; // --cm-ease-spring
const DURATION = 320;
const LABELS = '[x-show="!collapsed"]';
const ICONS = '.bo-nav-link > i:first-child, .bo-nav-head .bo-nav-toggle';

const shown = (el) => el.getClientRects().length > 0;
const settle = (anims) => Promise.all(anims.map((a) => a.finished)).catch(() => {});

// apply(): cambia el estado de Alpine y espera su $nextTick
async function toggle(nav, apply) {
    const aside = nav.querySelector('.bo-sidebar');
    const content = nav.nextElementSibling;
    if (prefersReducedMotion() || !aside || !content || !shown(aside)) {
        await apply();
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

    const after = nav.getBoundingClientRect().width;
    const wide = Math.max(before, after);
    const clip = (w) => `inset(0px ${wide - w}px 0px 0px)`;
    const opts = { duration: DURATION, easing: EASE };

    // El panel conserva el ancho mayor durante la animación y se recorta
    aside.style.width = `${wide}px`;
    const anims = [
        aside.animate([{ clipPath: clip(before) }, { clipPath: clip(after) }], opts),
        content.animate([{ transform: `translateX(${before - after}px)` }, { transform: 'none' }], opts),
    ];
    icons.forEach((icon, n) => {
        const dx = iconX[n] - icon.getBoundingClientRect().left;
        if (dx) anims.push(icon.animate([{ transform: `translateX(${dx}px)` }, { transform: 'none' }], opts));
    });

    // Al expandir, los textos entran escalonados detrás del borde del panel
    if (after > before) {
        [...aside.querySelectorAll(LABELS)].filter(shown).forEach((el, n) => el.animate(
            [{ opacity: 0, transform: 'translateX(-8px)' }, { opacity: 1, transform: 'none' }],
            { duration: 240, delay: 60 + n * 14, easing: EASE, fill: 'backwards' },
        ));
    }

    const arrow = nav.querySelector('.bo-nav-head .bo-nav-toggle i');
    arrow?.animate([{ transform: 'rotate(180deg) scale(.6)' }, { transform: 'none' }], { duration: 420, easing: SPRING });

    await settle(anims);
    aside.style.width = '';
    delete nav.dataset.boAnimating;
}

// Ícono que gira y rebota al cambiar (tema)
function spin(icon) {
    if (!icon || prefersReducedMotion()) return;
    icon.animate([{ transform: 'rotate(-120deg) scale(.4)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 420, easing: SPRING });
}

export function initBackofficeNav() {
    window.boSidebar = { toggle, spin };
    // La entrada escalonada de la barra corre una vez por carga completa; con wire:navigate
    // el <html> se conserva y la marca evita repetirla en cada cambio de página.
    // También al llegar navegando (p. ej. del login al dashboard, donde app.js ya corrió).
    const root = document.documentElement;
    const markSeen = () => {
        if (document.body.classList.contains('bo') && !root.classList.contains('bo-nav-seen')) {
            setTimeout(() => root.classList.add('bo-nav-seen'), 1200);
        }
    };
    markSeen();
    document.addEventListener('livewire:navigated', markSeen);
}
