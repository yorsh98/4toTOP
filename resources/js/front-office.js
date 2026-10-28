// Front office público (body.fo): tema, reloj, título cromado, reveal, navegación
// por secciones, drawers, buscador Ctrl K, formulario de oficios y apertura de módulos.
import { bump, movePill, prefersReducedMotion, withViewTransition } from './lib/chat-motion.js';
import { initAppOpen } from './lib/app-open.js';

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
// Búsqueda sin tildes ni mayúsculas: "garantia" encuentra "Garantía"
const normalize = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

function initTheme() {
    const root = document.documentElement;
    $$('[data-fo-theme]').forEach((btn) => {
        const sync = () => btn.setAttribute('aria-label', root.dataset.theme === 'light' ? 'Cambiar a tema oscuro' : 'Cambiar a tema claro');
        sync();
        btn.addEventListener('click', () => {
            root.dataset.theme = root.dataset.theme === 'light' ? 'dark' : 'light';
            try { localStorage.setItem('fo-theme', root.dataset.theme); } catch { /* sin almacenamiento */ }
            sync();
        });
    });
}

function initClock() {
    const clock = $('[data-fo-clock]');
    if (!clock) return;
    const date = $('[data-fo-date]');
    const time = new Intl.DateTimeFormat('es-CL', { timeZone: 'America/Santiago', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    const day = new Intl.DateTimeFormat('es-CL', { timeZone: 'America/Santiago', weekday: 'long', day: 'numeric', month: 'long' });
    const tick = () => {
        const now = new Date();
        clock.textContent = time.format(now);
        clock.setAttribute('datetime', now.toISOString());
        if (date) {
            const text = day.format(now).replace(',', '');
            date.textContent = text.charAt(0).toUpperCase() + text.slice(1);
        }
    };
    tick();
    setInterval(tick, 1000);
}

// Fondo: el Centro de Justicia avanza de izquierda a derecha según el scroll.
// La foto persigue la posición del scroll con inercia, para que la rueda del mouse
// no la mueva a saltos. Solo se toca el transform de la imagen: no recalcula estilos.
function initBackdrop() {
    const img = $('.fo-backdrop img');
    if (!img || prefersReducedMotion()) return;
    const root = document.documentElement;
    const SETTLE_MS = 220; // cuánto tarda en alcanzar el scroll: más alto, más suave
    let range = 0;
    let target = 0;
    let current = null;
    let last = 0;
    let frame = 0;

    const measure = () => {
        range = img.offsetWidth - innerWidth;
        const max = root.scrollHeight - innerHeight;
        target = max > 0 ? Math.min(1, Math.max(0, scrollY / max)) : 0;
    };

    const paint = (pan) => {
        img.style.transform = `translate3d(${((pan - 1) * range).toFixed(2)}px, 0, 0)`;
    };

    const step = (now) => {
        const dt = last ? Math.min(now - last, 64) : 16;
        last = now;
        current += (target - current) * (1 - Math.exp(-dt / SETTLE_MS));
        if (Math.abs(target - current) < 0.0002) current = target;
        paint(current);
        frame = current === target ? 0 : requestAnimationFrame(step);
        if (!frame) last = 0;
    };

    const queue = () => {
        measure();
        if (!frame) frame = requestAnimationFrame(step);
    };

    measure();
    current = target;
    paint(current);
    addEventListener('scroll', queue, { passive: true });
    addEventListener('resize', () => { measure(); paint(current); });
}

// Foco de luz que sigue al puntero sobre el título (portafolio)
function initChrome() {
    const title = $('[data-fo-chrome]');
    if (!title || prefersReducedMotion() || !matchMedia('(pointer: fine)').matches) return;
    let frame = 0;
    document.addEventListener('pointermove', (e) => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => {
            const r = title.getBoundingClientRect();
            title.style.setProperty('--mx', `${e.clientX - r.left}px`);
            title.style.setProperty('--my', `${e.clientY - r.top}px`);
        });
    }, { passive: true });
}

// Aparición al hacer scroll; las tarjetas que entran juntas lo hacen una a una
function initReveal() {
    const items = $$('[data-reveal]');
    const release = (el) => {
        el.removeAttribute('data-reveal');
        el.classList.remove('in');
        el.style.removeProperty('--delay');
    };
    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
        items.forEach(release);
        return;
    }
    const io = new IntersectionObserver((entries) => {
        let queue = 0;
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            if (el.dataset.reveal === 'seq') el.style.setProperty('--delay', `${queue++ * 90}ms`);
            el.classList.add('in');
            // Al terminar, devuelve el control a cm-lift / cm-press
            el.addEventListener('transitionend', () => release(el), { once: true });
            io.unobserve(el);
        });
    }, { threshold: 0.15 });
    items.forEach((el) => io.observe(el));
}

// Píldora deslizante bajo la sección visible
function initNav() {
    const nav = $('[data-fo-nav]');
    const sections = $$('[data-fo-section]');
    if (!nav || !sections.length) return;
    const pill = $('.cm-pill', nav);
    const links = new Map($$('[data-section]', nav).map((a) => [a.dataset.section, a]));
    let current = null;

    const activate = (id) => {
        current = links.get(id) ?? null;
        links.forEach((a) => (a === current ? a.setAttribute('aria-current', 'true') : a.removeAttribute('aria-current')));
        pill.classList.toggle('is-on', Boolean(current));
        if (current) movePill(pill, current);
    };

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => entry.isIntersecting && activate(entry.target.id));
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach((s) => io.observe(s));

    // Sobre la portada (antes de la primera sección) no hay ítem activo
    const hero = $('.fo-hero');
    if (hero) {
        new IntersectionObserver(([entry]) => entry.isIntersecting && activate(null), { rootMargin: '-45% 0px -50% 0px' }).observe(hero);
    }
    addEventListener('resize', () => current && movePill(pill, current));
}

// Drawers (Turnos, Encargados, Estado de audiencias)
function initDrawers() {
    let opener = null;
    let openId = null;

    const parts = (id) => ({ drawer: $(`#fo-drawer-${id}`), scrim: $(`.fo-scrim[data-fo-close="${id}"]`) });

    const close = () => {
        if (!openId) return;
        const { drawer, scrim } = parts(openId);
        drawer.classList.remove('is-open');
        scrim.classList.remove('is-open');
        drawer.inert = true;
        document.documentElement.style.overflow = '';
        openId = null;
        opener?.focus({ preventScroll: true });
    };

    const open = (id, targetId = null) => {
        const { drawer, scrim } = parts(id);
        if (!drawer) return false;
        close();
        opener = document.activeElement;
        openId = id;
        drawer.inert = false;
        drawer.classList.add('is-open');
        scrim.classList.add('is-open');
        document.documentElement.style.overflow = 'hidden';
        $('[data-fo-close]', drawer)?.focus({ preventScroll: true });
        const target = targetId && document.getElementById(targetId);
        if (target) target.scrollIntoView({ block: 'start', behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
        return true;
    };

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-fo-open]');
        if (trigger) {
            if (open(trigger.dataset.foOpen, trigger.dataset.foTarget)) e.preventDefault();
            return;
        }
        if (e.target.closest('[data-fo-close]')) close();
    });
    document.addEventListener('keydown', (e) => e.key === 'Escape' && close());

    // Abre el drawer pedido desde otra página (/#estado)
    const fromHash = location.hash.slice(1);
    if (fromHash && open(fromHash)) history.replaceState(null, '', location.pathname);

    return { open };
}

// Buscador de accesos (Ctrl K / Cmd K)
function initPalette() {
    const dialog = $('#fo-palette');
    if (!dialog) return;
    const input = $('[data-fo-palette-input]', dialog);
    const items = $$('[data-fo-palette-item]', dialog);
    const groups = $$('[data-fo-palette-group]', dialog);
    const empty = $('[data-fo-palette-empty]', dialog);
    const count = $('[data-fo-palette-count]', dialog);
    const haystack = new Map(items.map((a) => [a, normalize(a.dataset.text)]));
    let visible = items;
    let active = 0;

    const select = (i) => {
        if (!visible.length) return;
        active = (i + visible.length) % visible.length;
        visible.forEach((a, n) => a.setAttribute('aria-selected', String(n === active)));
        visible[active].scrollIntoView({ block: 'nearest' });
        input.setAttribute('aria-activedescendant', visible[active].id);
    };

    const filter = () => {
        const words = normalize(input.value).split(/\s+/).filter(Boolean);
        visible = items.filter((a) => {
            const match = words.every((w) => haystack.get(a).includes(w));
            a.parentElement.hidden = !match;
            a.setAttribute('aria-selected', 'false');
            return match;
        });
        groups.forEach((g) => { g.hidden = !$$('[data-fo-palette-item]', g).some((a) => !a.parentElement.hidden); });
        empty.hidden = visible.length > 0;
        $('[data-fo-palette-query]', empty).textContent = input.value;
        if (count.textContent !== String(visible.length)) {
            count.textContent = visible.length;
            $('[data-fo-palette-noun]', dialog).textContent = visible.length === 1 ? 'acceso' : 'accesos';
            bump(count);
        }
        select(0);
    };

    items.forEach((a, i) => { a.id = `fo-cmd-${i}`; });

    const show = () => {
        if (dialog.open) return;
        input.value = '';
        filter();
        dialog.showModal();
        input.focus();
    };

    $$('[data-fo-palette]').forEach((btn) => btn.addEventListener('click', show));
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            dialog.open ? dialog.close() : show();
        }
    });
    input.addEventListener('input', filter);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') { e.preventDefault(); select(active + 1); }
        if (e.key === 'ArrowUp') { e.preventDefault(); select(active - 1); }
        if (e.key === 'Enter' && visible[active]) { e.preventDefault(); visible[active].click(); }
    });
    // Ítems que abren un drawer en esta misma página
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
        if (e.target.closest('[data-fo-open]')) dialog.close();
    });
    items.forEach((a) => a.addEventListener('mousemove', () => {
        const i = visible.indexOf(a);
        if (i !== -1 && i !== active) select(i);
    }));
}

// Filtro instantáneo de las páginas de listado (<x-public.list-page>)
function initListFilter() {
    const bar = $('[data-fo-filter-bar]');
    const scope = $('[data-fo-filter-scope]');
    if (!bar || !scope) return;
    const input = $('[data-fo-filter]', bar);
    const count = $('[data-fo-filter-count]', bar);
    const of = $('[data-fo-filter-of]', bar);
    const noun = $('[data-fo-filter-noun]', bar);
    const empty = $('[data-fo-filter-empty]', scope);
    const items = $$('[data-fo-item]', scope);
    const groups = $$('[data-fo-group]', scope);
    const haystack = new Map(items.map((li) => [li, normalize(li.dataset.text)]));

    const filter = () => {
        const words = normalize(input.value).split(/\s+/).filter(Boolean);
        let shown = 0;
        items.forEach((li) => {
            const match = words.every((w) => haystack.get(li).includes(w));
            li.hidden = !match;
            if (match) shown++;
        });
        groups.forEach((g) => { g.hidden = !$$('[data-fo-item]', g).some((li) => !li.hidden); });
        empty.hidden = shown > 0;
        $('[data-fo-filter-query]', empty).textContent = input.value;
        of.hidden = words.length === 0;
        noun.textContent = (words.length ? items.length : shown) === 1 ? bar.dataset.nounOne : bar.dataset.nounMany;
        if (count.textContent !== String(shown)) {
            count.textContent = shown;
            bump(count);
        }
    };

    const clear = () => { input.value = ''; filter(); input.focus(); };

    input.addEventListener('input', filter);
    input.addEventListener('keydown', (e) => { if (e.key === 'Escape' && input.value) { e.stopPropagation(); clear(); } });
    $('[data-fo-filter-clear]', scope)?.addEventListener('click', clear);
    // "/" enfoca el filtro, salvo que ya se esté escribiendo en otro campo
    document.addEventListener('keydown', (e) => {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
        if (e.target.closest('input, textarea, select, [contenteditable="true"]') || $('dialog[open]')) return;
        e.preventDefault();
        input.focus();
    });
}

// Oficios y libertades: selector de tipo, envío único y copiar número
function initOficios() {
    const form = $('[data-fo-oficios]');
    if (form) {
        const seg = $('[data-fo-seg]', form);
        const pill = $('.cm-pill', seg);
        const label = $('[data-fo-submit-label]', form);
        const submit = $('[data-fo-submit]', form);
        const checked = () => $('input:checked', seg);
        const place = () => movePill(pill, checked().closest('label'));

        place();
        addEventListener('resize', place);
        seg.addEventListener('change', () => {
            place();
            withViewTransition(() => { label.textContent = `Pedir número de ${checked().value}`; });
        });
        // Evita pedir dos números por un doble clic
        form.addEventListener('submit', () => {
            submit.disabled = true;
            label.textContent = 'Pidiendo número…';
        });
    }

    $$('[data-fo-copy]').forEach((btn) => btn.addEventListener('click', async () => {
        const text = $('[data-fo-copy-label]', btn);
        try {
            await navigator.clipboard.writeText(btn.dataset.foCopy);
            text.textContent = 'Copiado';
        } catch {
            text.textContent = 'No se pudo copiar; selecciónalo a mano';
        }
        setTimeout(() => { text.textContent = 'Copiar número'; }, 2400);
    }));
}

export function initFrontOffice() {
    if (!document.body.classList.contains('fo')) return;
    initTheme();
    initClock();
    initBackdrop();
    initChrome();
    initReveal();
    initNav();
    initDrawers();
    initPalette();
    initListFilter();
    initOficios();
    // Tarjetas de módulos: se abren como app (las páginas públicas navegan con recarga completa)
    initAppOpen({ crossDocument: true });
}
