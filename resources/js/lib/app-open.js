/* ==========================================================================
   app-open.js — transición "abrir app" estilo iPhone entre vistas
   ES module sin dependencias. Pensado para Livewire 3 (wire:navigate), pero
   funciona con cualquier router: solo necesita una función navigate(url) y un
   evento que avise cuando la vista nueva ya está en el DOM.

   Abrir:  la tarjeta crece hasta llenar la pantalla (clip-path), su ícono
           viaja al centro (pantalla de carga) y el inicio se acerca y se
           desvanece detrás. Al llegar la vista nueva, la capa se funde y el
           contenido entra escalonado.
   Cerrar: al volver a una vista que tiene la tarjeta de origen, la pantalla
           se recoge dentro de esa tarjeta y el inicio vuelve a su escala.

   Marcado mínimo:
     <main data-app-shell> … </main>                         contenedor del inicio
     <a href="/progra" wire:navigate data-app-open
        data-app-color="#0A2E5D">                            tarjeta (color opcional)
        <span data-app-icon>…svg…</span> Programación
     </a>
     <main data-app-view> … </main>                          contenedor de cada vista

   Uso (resources/js/app.js):
     import { initAppOpen } from './lib/app-open';
     initAppOpen();

   Entre documentos (crossDocument: true, navegación con recarga completa):
     abrir guarda la URL destino en sessionStorage y navega con location;
     el <head> de la página nueva pone html.ao-cover (pantalla del color de la
     capa) y data-ao-arrive="open" | "close" antes del primer pintado, y este
     módulo la revela o la recoge en la tarjeta. Volver desde el bfcache recoge
     la capa que quedó abierta. Ver el script del <head> en
     resources/views/components/public/layout.blade.php.
   ========================================================================== */

import { prefersReducedMotion } from './chat-motion';

const FULL = 'inset(0px 0px 0px 0px round 0px)';

// Claves compartidas con el script del <head> del layout público
export const ARRIVE_KEY = 'ao-open';
export const LAST_KEY = 'ao-last';

const store = (key, value) => {
  try {
    value === null ? sessionStorage.removeItem(key) : sessionStorage.setItem(key, value);
  } catch { /* sin almacenamiento: navega sin transición de llegada */ }
};

const cssVar = (name, fallback) =>
  getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;

// El minificador de CSS reescribe 560ms como .56s: se aceptan ambas unidades
const msVar = (name, fallback) => {
  const raw = cssVar(name, '');
  const n = parseFloat(raw);
  if (!Number.isFinite(n)) return fallback;
  return /\ds$/.test(raw) ? n * 1000 : n;
};

// Clave de comparación de URLs: ruta + query + hash, sin barra final
const keyOf = (href) => {
  try {
    const u = new URL(href, location.href);
    return (u.pathname.replace(/\/+$/, '') || '/') + u.search + u.hash;
  } catch {
    return String(href);
  }
};

const viewport = () => ({
  w: document.documentElement.clientWidth,
  h: document.documentElement.clientHeight,
});

const insetFor = (r, radius) => {
  const { w, h } = viewport();
  return `inset(${r.top}px ${w - r.right}px ${h - r.bottom}px ${r.left}px round ${radius}px)`;
};

const radiusOf = (el) => parseFloat(getComputedStyle(el).borderTopLeftRadius) || 0;

// Transformación que lleva el ícono desde su posición al centro de la pantalla
const iconToCenter = (iconRect, scale) => {
  const { w, h } = viewport();
  const dx = w / 2 - (iconRect.left + iconRect.width / 2);
  const dy = h * 0.46 - (iconRect.top + iconRect.height / 2);
  return `translate(${dx}px, ${dy}px) scale(${scale})`;
};

const originFor = (shellEl, rect) => {
  const s = shellEl.getBoundingClientRect();
  return `${rect.left + rect.width / 2 - s.left}px ${rect.top + rect.height / 2 - s.top}px`;
};

/* La capa se cuelga de <html>, no de <body>: wire:navigate reemplaza el
   <body> completo y la capa tiene que sobrevivir al cambio. */
function buildLayer(color, iconEl, iconRect) {
  const layer = document.createElement('div');
  layer.className = 'ao-layer';
  layer.setAttribute('aria-hidden', 'true');
  layer.style.background = color;
  const icon = iconRect?.width ? addIcon(layer, iconEl, iconRect) : null;
  document.documentElement.appendChild(layer);
  return { layer, icon };
}

// Copia del ícono de la tarjeta, ubicada exactamente sobre el original.
// Fuera del <body> no aplican los estilos con ámbito (.fo …): se copian los computados.
function addIcon(layer, iconEl, iconRect) {
  const icon = iconEl.cloneNode(true);
  const cs = getComputedStyle(iconEl);
  icon.removeAttribute('id');
  icon.querySelectorAll('[id]').forEach((n) => n.removeAttribute('id'));
  icon.classList.add('ao-icon');
  ['color', 'background', 'border', 'borderRadius', 'boxShadow', 'fontSize', 'lineHeight']
    .forEach((p) => { icon.style[p] = cs[p]; });
  Object.assign(icon.style, {
    left: `${iconRect.left}px`,
    top: `${iconRect.top}px`,
    width: `${iconRect.width}px`,
    height: `${iconRect.height}px`,
  });
  layer.appendChild(icon);
  return icon;
}

const settle = (anims) => Promise.all(anims.map((a) => a.finished)).catch(() => {});

export function initAppOpen({
  selector = '[data-app-open]',
  shellSelector = '[data-app-shell]',
  viewSelector = '[data-app-view]',
  crossDocument = false,
  navigate = (url) =>
    !crossDocument && window.Livewire?.navigate ? window.Livewire.navigate(url) : window.location.assign(url),
  navigatedEvent = 'livewire:navigated',
  timeout = 10000,
} = {}) {
  let busy = false;
  let opening = null; // { layer, link, timer }
  let currentKey = keyOf(location.href);

  const timings = () => ({
    open: msVar('--ao-t-open', 560),
    close: msVar('--ao-t-close', 480),
    reveal: msVar('--ao-t-reveal', 280),
    ease: cssVar('--ao-ease', 'cubic-bezier(.32, .72, 0, 1)'),
    iconScale: parseFloat(cssVar('--ao-icon-scale', '1.5')) || 1.5,
    surface: cssVar('--ao-surface', '#f4f7fb'),
  });

  /* ---------- Abrir ---------- */
  async function open(link) {
    busy = true;
    document.documentElement.classList.add('ao-opening');
    const t = timings();
    const url = link.href;
    const rect = link.getBoundingClientRect();
    const radius = radiusOf(link);
    const iconEl = link.querySelector('[data-app-icon]');
    const iconRect = iconEl?.getBoundingClientRect();
    const { layer, icon } = buildLayer(link.dataset.appColor || t.surface, iconEl, iconRect);
    const shell = document.querySelector(shellSelector);
    const opts = { duration: t.open, easing: t.ease, fill: 'forwards' };

    link.style.visibility = 'hidden';
    const anims = [layer.animate([{ clipPath: insetFor(rect, radius) }, { clipPath: FULL }], opts)];
    if (icon) {
      anims.push(icon.animate([{ transform: 'none' }, { transform: iconToCenter(iconRect, t.iconScale) }], opts));
    }
    if (shell) {
      shell.style.transformOrigin = originFor(shell, rect);
      anims.push(shell.animate(
        [{ transform: 'scale(1)', opacity: 1 }, { transform: 'scale(1.08)', opacity: 0 }],
        { ...opts, duration: t.open * 0.85 },
      ));
    }

    await settle(anims);

    // Si la navegación nunca avisa (error de red, recarga completa), limpiar
    const timer = setTimeout(() => {
      if (!opening) return;
      link.style.visibility = '';
      document.documentElement.classList.remove('ao-opening');
      shell?.getAnimations().forEach((a) => a.cancel());
      fadeOut(opening.layer, t.reveal);
      opening = null;
      busy = false;
    }, timeout);

    opening = { layer, link, timer };
    if (crossDocument) {
      store(ARRIVE_KEY, keyOf(url));
      store(LAST_KEY, keyOf(url));
    }
    navigate(url);
  }

  /* ---------- Revelar la vista nueva ---------- */
  function reveal() {
    const t = timings();
    const { layer, timer } = opening;
    clearTimeout(timer);
    opening = null;
    document.documentElement.classList.remove('ao-opening');
    revealView(layer, t);
  }

  function revealView(layer, t) {
    const view = document.querySelector(viewSelector);
    if (view) {
      [...view.children].forEach((c, i) => c.style.setProperty('--ao-i', i));
      view.classList.add('ao-enter');
      setTimeout(() => view.classList.remove('ao-enter'), 1400);
    }
    fadeOut(layer, t.reveal).then(() => { busy = false; });
  }

  function fadeOut(layer, duration) {
    const a = layer.animate([{ opacity: 1 }, { opacity: 0 }], { duration, easing: 'ease-out', fill: 'forwards' });
    return a.finished.catch(() => {}).then(() => layer.remove());
  }

  /* ---------- Cerrar (volver a la vista con la tarjeta de origen) ---------- */
  function closeTo(link) {
    busy = true;
    const t = timings();
    // Cubre la pantalla en el mismo frame del cambio para que no se vea un salto
    const { layer } = buildLayer(link.dataset.appColor || t.surface, null, null);
    layer.style.clipPath = FULL;
    link.style.visibility = 'hidden';

    requestAnimationFrame(async () => {
      link.scrollIntoView({ block: 'nearest' });
      const rect = link.getBoundingClientRect();
      const radius = radiusOf(link);
      const iconEl = link.querySelector('[data-app-icon]');
      const iconRect = iconEl?.getBoundingClientRect();
      const shell = document.querySelector(shellSelector);
      const opts = { duration: t.close, easing: t.ease, fill: 'forwards' };

      const icon = iconEl && iconRect ? addIcon(layer, iconEl, iconRect) : null;

      const anims = [layer.animate([{ clipPath: FULL }, { clipPath: insetFor(rect, radius) }], opts)];
      if (icon) {
        anims.push(icon.animate([{ transform: iconToCenter(iconRect, t.iconScale) }, { transform: 'none' }], opts));
      }
      if (shell) {
        shell.style.transformOrigin = originFor(shell, rect);
        anims.push(shell.animate(
          [{ transform: 'scale(1.08)', opacity: 0 }, { transform: 'scale(1)', opacity: 1 }],
          { ...opts, fill: 'none' },
        ));
      }

      await settle(anims);
      link.style.visibility = '';
      layer.remove();
      busy = false;
    });
  }

  /* ---------- Eventos ---------- */
  const onClick = (e) => {
    const link = e.target.closest?.(selector);
    if (!link) return;
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;
    if (busy) { e.preventDefault(); e.stopPropagation(); return; }
    if (prefersReducedMotion()) return; // deja que wire:navigate haga su cambio normal

    // Fase de captura + stopPropagation: evita que wire:navigate navegue antes
    // de que termine la animación. La navegación la lanza open() al final.
    e.preventDefault();
    e.stopPropagation();
    open(link);
  };

  const onNavigated = () => {
    const prevKey = currentKey;
    currentKey = keyOf(location.href);

    if (opening) { reveal(); return; }
    if (busy || prefersReducedMotion() || prevKey === currentKey) return;

    const origin = [...document.querySelectorAll(selector)].find((a) => keyOf(a.href) === prevKey);
    if (origin) closeTo(origin);
  };

  /* ---------- Entre documentos ---------- */
  // Llegada: el <head> ya cubrió la pantalla con html.ao-cover; se cambia por una
  // capa real en el mismo frame y se revela la vista o se recoge en la tarjeta.
  function arrive() {
    const root = document.documentElement;
    const mode = root.dataset.aoArrive;
    if (!mode) return;

    // Al abrir, la cubierta y la entrada escalonada son CSS puro (app-open.css):
    // empiezan en el primer pintado y aquí solo se limpian al terminar.
    if (mode === 'open') {
      const anims = root.getAnimations({ subtree: true }).filter((a) => a.animationName?.startsWith('ao-'));
      settle(anims).then(() => {
        root.classList.remove('ao-cover');
        delete root.dataset.aoArrive;
      });
      return;
    }

    delete root.dataset.aoArrive;
    const t = timings();
    const last = root.dataset.aoFrom;
    delete root.dataset.aoFrom;
    const origin = last && [...document.querySelectorAll(selector)].find((a) => keyOf(a.href) === last);

    if (origin) {
      origin.removeAttribute('data-reveal'); // la capa aterriza en la tarjeta: ya visible, sin aparición por scroll
      closeTo(origin);
    } else {
      busy = true;
      const { layer } = buildLayer(t.surface, null, null);
      layer.style.clipPath = FULL;
      fadeOut(layer, t.reveal).then(() => { busy = false; });
    }
    root.classList.remove('ao-cover');
  }

  // Atrás desde el bfcache: la página vuelve con la capa abierta; se recoge en su tarjeta
  const onPageShow = (e) => {
    if (!e.persisted || !opening) return;
    const { layer, link, timer } = opening;
    clearTimeout(timer);
    opening = null;
    store(LAST_KEY, null);
    document.documentElement.classList.remove('ao-opening');
    document.querySelector(shellSelector)?.getAnimations().forEach((a) => a.cancel());
    busy = false;
    if (prefersReducedMotion()) {
      link.style.visibility = '';
      layer.remove();
      return;
    }
    closeTo(link);
    layer.remove();
  };

  document.addEventListener('click', onClick, true);
  if (crossDocument) {
    window.addEventListener('pageshow', onPageShow);
    arrive();
  } else {
    document.addEventListener(navigatedEvent, onNavigated);
  }

  return () => {
    document.removeEventListener('click', onClick, true);
    document.removeEventListener(navigatedEvent, onNavigated);
    window.removeEventListener('pageshow', onPageShow);
  };
}
