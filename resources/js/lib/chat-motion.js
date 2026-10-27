/* ==========================================================================
   chat-motion.js — helpers de animación para chat de agentes con RAG
   ES module sin dependencias. Funciona con Vue 3, Alpine/Livewire, React o
   JS plano: cada helper recibe un elemento del DOM y no toca tu estado.
   ========================================================================== */

export const prefersReducedMotion = () =>
  typeof matchMedia === "function" && matchMedia("(prefers-reduced-motion: reduce)").matches;

/* ---------- Scroll inteligente ----------
   Solo baja al final si el usuario ya estaba cerca del final.
   Así no le quitas el scroll si está leyendo un mensaje anterior. */
export function createAutoScroll(feedEl, { threshold = 120 } = {}) {
  let stick = true;
  const onScroll = () => {
    stick = feedEl.scrollHeight - feedEl.scrollTop - feedEl.clientHeight < threshold;
  };
  feedEl.addEventListener("scroll", onScroll, { passive: true });
  return {
    scroll(force = false) {
      if (force || stick) feedEl.scrollTo({ top: feedEl.scrollHeight, behavior: prefersReducedMotion() ? "auto" : "smooth" });
    },
    destroy() { feedEl.removeEventListener("scroll", onScroll); },
  };
}

/* ---------- Indicador de escritura ---------- */
export function typingHTML() {
  return '<div class="cm-typing" role="status" aria-label="El agente está escribiendo"><span></span><span></span><span></span></div>';
}

/* ---------- Recuperación RAG visible ----------
   const r = createRetrieval(el, { label: "Buscando en la base documental…" });
   r.add("doc-1", "Ley 19.628");  r.done("doc-1");  r.destroy(); */
export function createRetrieval(container, { label = "Buscando en la base documental…", maxTitle = 32 } = {}) {
  container.innerHTML = `<div class="cm-retrieval"><span class="cm-shimmer"></span><div class="cm-chips"></div></div>`;
  container.querySelector(".cm-shimmer").textContent = label;
  const chips = container.querySelector(".cm-chips");
  const map = new Map();
  return {
    setLabel(text) { container.querySelector(".cm-shimmer").textContent = text; },
    add(id, title) {
      if (map.has(id)) return;
      const chip = document.createElement("span");
      chip.className = "cm-chip";
      const ck = document.createElement("span");
      ck.className = "cm-ck";
      chip.append(ck, document.createTextNode(title.length > maxTitle ? title.slice(0, maxTitle) + "…" : title));
      chips.appendChild(chip);
      map.set(id, chip);
    },
    done(id) { map.get(id)?.classList.add("is-done"); },
    doneAll() { map.forEach(c => c.classList.add("is-done")); },
    destroy() { container.innerHTML = ""; map.clear(); },
  };
}

/* ---------- Streaming con citas ----------
   Recibe trozos de texto tal como llegan del LLM (tokens) y los pinta con
   fundido por palabra. Detecta citas con el patrón [1], [2]… aunque lleguen
   partidas entre dos tokens.

   const s = createStreamRenderer(pEl, { onCite: n => abrirFuente(n) });
   s.push("El plazo es de 10 días [");  s.push("3] desde…");  s.end();

   Si tu chat renderiza Markdown, mira references/streaming.md. */
export function createStreamRenderer(target, {
  citePattern = /\[(\d{1,2})\]/,
  onCite = null,
  caret = true,
} = {}) {
  let buffer = "";
  let caretEl = null;
  let para = target;
  const reduced = prefersReducedMotion();
  // Si el destino no es un <p>, cada párrafo va en su propio <p>
  if (target.tagName !== "P") { para = document.createElement("p"); target.appendChild(para); }

  if (caret) {
    caretEl = document.createElement("span");
    caretEl.className = "cm-caret";
    para.appendChild(caretEl);
  }

  const insert = (node) => (caretEl && caretEl.parentNode === para ? para.insertBefore(node, caretEl) : para.appendChild(node));

  const newParagraph = () => {
    if (target.tagName === "P") { insert(document.createElement("br")); insert(document.createElement("br")); return; }
    const p = document.createElement("p");
    target.appendChild(p);
    para = p;
    if (caretEl) para.appendChild(caretEl);
  };

  const emitText = (text) => {
    for (const part of text.split(/(\n\n+|\s+)/)) {
      if (!part) continue;
      if (/^\n\n+$/.test(part)) newParagraph();
      else if (/^\s+$/.test(part)) insert(document.createTextNode(part));
      else if (reduced) insert(document.createTextNode(part));
      else { const w = document.createElement("span"); w.className = "cm-w"; w.textContent = part; insert(w); }
    }
  };

  const emitCite = (n) => {
    const b = document.createElement("button");
    b.type = "button";
    b.className = "cm-cite";
    b.dataset.cite = n;
    b.setAttribute("aria-label", `Ver fuente ${n}`);
    b.textContent = n;
    if (onCite) b.addEventListener("click", () => onCite(Number(n)));
    insert(b);
  };

  const flush = (final) => {
    const global = new RegExp(citePattern.source, "g");
    let last = 0, m;
    while ((m = global.exec(buffer))) {
      emitText(buffer.slice(last, m.index));
      emitCite(m[1]);
      last = global.lastIndex;
    }
    let rest = buffer.slice(last);
    // Guarda una cita que puede estar a medio llegar ("[" o "[1")
    const open = rest.lastIndexOf("[");
    let hold = "";
    if (!final && open !== -1 && /^\[\d{0,2}$/.test(rest.slice(open))) { hold = rest.slice(open); rest = rest.slice(0, open); }
    // Guarda la última palabra (y el espacio previo) por si sigue llegando:
    // así no se parte en dos spans ni se pierde un "\n\n" que llega en dos tokens
    if (!final && !hold) {
      hold = rest.match(/\s*\S*$/)[0];
      rest = rest.slice(0, rest.length - hold.length);
    }
    emitText(rest);
    buffer = hold;
  };

  return {
    push(chunk) { buffer += chunk; flush(false); },
    end() { flush(true); caretEl?.remove(); caretEl = null; },
    abort(note = "Respuesta detenida") {
      flush(true); caretEl?.remove(); caretEl = null;
      const em = document.createElement("em");
      em.className = "cm-stopped";
      em.textContent = ` · ${note}`;
      para.appendChild(em);
    },
  };
}

/* ---------- Citas ↔ fuentes ----------
   Al pasar el mouse o enfocar [n], resalta el elemento con data-source="n"
   dentro del mismo mensaje. Llamar una vez sobre el contenedor del chat. */
export function linkCitations(feedEl, { msgSelector = "[data-cm-msg]", sourceAttr = "data-source" } = {}) {
  const clear = () => feedEl.querySelectorAll(".is-linked").forEach(x => x.classList.remove("is-linked"));
  const light = (cite) => {
    clear();
    const scope = cite.closest(msgSelector) || feedEl;
    scope.querySelectorAll(`[${sourceAttr}="${cite.dataset.cite}"]`).forEach(x => x.classList.add("is-linked"));
  };
  const over = e => { const c = e.target.closest(".cm-cite"); c ? light(c) : clear(); };
  const focus = e => { const c = e.target.closest(".cm-cite"); if (c) light(c); };
  feedEl.addEventListener("mouseover", over);
  feedEl.addEventListener("focusin", focus);
  feedEl.addEventListener("focusout", clear);
  return () => {
    feedEl.removeEventListener("mouseover", over);
    feedEl.removeEventListener("focusin", focus);
    feedEl.removeEventListener("focusout", clear);
  };
}

/* ---------- Contador que salta ---------- */
export function bump(el) {
  if (!el) return;
  el.classList.remove("cm-bump");
  void el.offsetWidth; // reinicia la animación
  el.classList.add("cm-bump");
}

/* ---------- Píldora deslizante del agente activo ---------- */
export function movePill(pillEl, activeEl) {
  if (!pillEl || !activeEl) return;
  pillEl.style.height = activeEl.offsetHeight + "px";
  pillEl.style.width = activeEl.offsetWidth + "px";
  pillEl.style.transform = `translate(${activeEl.offsetLeft}px, ${activeEl.offsetTop}px)`;
}

/* ---------- Cambio de vista con View Transitions ----------
   await withViewTransition(() => { cambiar agente / vista / modo })
   Resuelve cuando el DOM nuevo ya está aplicado (no espera el fin de la animación),
   así puedes empezar a pedir la respuesta mientras el input todavía viaja.
   Si necesitas esperar a que termine la animación: { waitFor: "finished" }.
   Cae a cambio directo si el navegador no lo soporta o hay movimiento reducido. */
export function withViewTransition(update, { waitFor = "update" } = {}) {
  if (typeof document !== "undefined" && document.startViewTransition && !prefersReducedMotion()) {
    const t = document.startViewTransition(update);
    return (waitFor === "finished" ? t.finished : t.updateCallbackDone).catch(() => {});
  }
  return Promise.resolve(update());
}

/* ---------- Nuevo chat ---------- */

/* Título provisorio a partir de la primera pregunta (úsalo si el backend no
   genera uno con el LLM). */
export function fallbackTitle(text, max = 34) {
  let t = String(text).replace(/[¿?¡!.]/g, "").replace(/\s+/g, " ").trim();
  t = t.charAt(0).toUpperCase() + t.slice(1);
  if (t.length > max) { const cut = t.lastIndexOf(" ", max); t = t.slice(0, cut > 10 ? cut : max) + "…"; }
  return t || "Nuevo chat";
}

/* Escribe el título letra por letra en el ítem del historial.
   await typeTitle(el, "Plazo de cierre de investigación") */
export async function typeTitle(el, title, { speed = 24 } = {}) {
  if (!el) return;
  el.classList.remove("cm-title-pending");
  if (prefersReducedMotion()) { el.textContent = title; return; }
  el.textContent = "";
  const caret = document.createElement("span");
  caret.className = "cm-tcaret";
  el.appendChild(caret);
  for (const ch of title) {
    caret.before(ch);
    await new Promise(r => setTimeout(r, speed));
  }
  await new Promise(r => setTimeout(r, 400));
  caret.remove();
}

/* Marca los mensajes de un chat restaurado para que no vuelvan a animarse. */
export function markStatic(feedEl, selector = "[data-cm-msg]") {
  feedEl.querySelectorAll(selector).forEach(m => m.classList.add("cm-static"));
}

/* ---------- Lector de SSE sobre fetch (permite POST y AbortController) ----------
   await readSSE(response, {
     retrieval_start: d => …, retrieval_doc: d => …, retrieval_done: d => …,
     token: d => …, sources: d => …, done: d => …, error: d => …
   }); */
export async function readSSE(response, handlers = {}) {
  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buf = "";
  for (;;) {
    const { value, done } = await reader.read();
    if (done) break;
    buf += decoder.decode(value, { stream: true });
    let idx;
    while ((idx = buf.indexOf("\n\n")) !== -1) {
      const raw = buf.slice(0, idx);
      buf = buf.slice(idx + 2);
      let event = "message", data = "";
      for (const line of raw.split("\n")) {
        if (line.startsWith("event:")) event = line.slice(6).trim();
        else if (line.startsWith("data:")) data += (data ? "\n" : "") + line.slice(5).trimStart();
      }
      let parsed = data;
      try { parsed = JSON.parse(data); } catch { /* texto plano */ }
      handlers[event]?.(parsed);
    }
  }
}

/* ---------- Orquestador completo de un turno ----------
   Junta todo: escribiendo → recuperación → streaming → fuentes.
   Úsalo tal cual o como referencia para tu propio store/componente. */
export function createTurn(bubbleEl, { onCite, renderSources, counterEl, autoScroll } = {}) {
  bubbleEl.innerHTML = typingHTML();
  let retrieval = null, stream = null, body = null;
  const scroll = () => autoScroll?.scroll();

  const ensureStream = () => {
    if (stream) return stream;
    retrieval?.destroy();
    body = document.createElement("div");
    body.className = "cm-body";
    bubbleEl.innerHTML = "";
    bubbleEl.appendChild(body);
    stream = createStreamRenderer(body, { onCite });
    return stream;
  };

  return {
    handlers: {
      retrieval_start: d => { retrieval = createRetrieval(bubbleEl, { label: d?.label }); scroll(); },
      retrieval_doc: d => { retrieval?.add(d.id, d.title); if (d.done) retrieval?.done(d.id); scroll(); },
      retrieval_done: () => retrieval?.doneAll(),
      token: d => { ensureStream().push(typeof d === "string" ? d : d.text); scroll(); },
      sources: list => {
        ensureStream().end();
        if (renderSources) {
          const html = renderSources(list);
          if (typeof html === "string") bubbleEl.insertAdjacentHTML("beforeend", html);
          else if (html) bubbleEl.appendChild(html);
        }
        bump(counterEl);
        scroll();
      },
      done: () => { stream?.end(); scroll(); },
    },
    abort() { if (stream) stream.abort(); else bubbleEl.innerHTML = '<p class="cm-stopped">Respuesta detenida.</p>'; },
  };
}
