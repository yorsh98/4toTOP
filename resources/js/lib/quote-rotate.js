// Botón "otra frase": cambia la frase del día por otra al azar, sin recargar.
// Delegado en document para que siga funcionando después de wire:navigate.
// Usa temporizadores fijos (no promesas de animación) para que el botón nunca quede trabado.
import { prefersReducedMotion } from './chat-motion.js';

const OUT_MS = 140;
const IN_MS = 260;

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-quote-rotate]');
    const quote = button?.closest('[data-quote]');
    const text = quote?.querySelector('[data-quote-text]');
    if (!text || quote.dataset.rotating) return;

    let frases;
    try { frases = JSON.parse(quote.dataset.frases); } catch { return; }
    const opciones = frases.filter((frase) => frase !== text.textContent.trim());
    if (!opciones.length) return;
    const nueva = opciones[Math.floor(Math.random() * opciones.length)];

    button.style.setProperty('--turns', Number(button.style.getPropertyValue('--turns') || 0) + 1);
    if (prefersReducedMotion()) { text.textContent = nueva; return; }

    quote.dataset.rotating = 'out';
    setTimeout(() => {
        text.textContent = nueva;
        quote.dataset.rotating = 'in';
        setTimeout(() => delete quote.dataset.rotating, IN_MS);
    }, OUT_MS);
});
