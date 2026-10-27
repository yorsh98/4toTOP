# Implementación: rediseño UI/UX del cortador de audio (modal guiado por pasos)

Fecha: 26 de octubre de 2026. Base: «Cortar audio» ya implementado y en `main` (`964ae80`), descrito en [Implementacion_CortarAudios.md](Implementacion_CortarAudios.md) y [file-conversions.md](file-conversions.md#cortar-audio).

Es una orden de trabajo para un agente. Se apoya en dos skills, y el agente debe cargarlas antes de escribir la interfaz:

- **`frontend-design`**: dirección visual. Las decisiones de diseño de la sección 3 ya pasaron por su proceso de plan y crítica, así que no hay que rediseñarlas, solo ejecutarlas.
- **`chat-motion`**: sistema de movimiento. Se copian sus assets tal cual (paso 1) y se usan sus tokens, reglas y helpers (`withViewTransition`, `bump`, `--cm-i`, `.cm-press`, `.cm-focus-ring`, `.cm-rise`). Sus patrones de chat (streaming, citas, historial) **no aplican aquí**.

No cambies la lógica de corte: `AudioCutTool`, `AudioInspector`, las rutas y la cola quedan como están. Las únicas excepciones son las de la sección 5.

---

## 1) Problema y objetivo

Hoy el cortador está mezclado con el formulario general del conversor: nombre, archivo, onda, tabla de regiones, modo, fundido, formato y botón, todo en una columna larga. El usuario no sabe en qué orden hacer las cosas ni qué le pasará a su audio, y tras convertir tiene que bajar hasta «Archivos de esta sesión» para encontrar la descarga.

**Usuarios:** funcionarios del juzgado que necesitan sacar o quitar un trozo de una grabación de audiencia, en general sin experiencia en edición de audio.

**Objetivo:** al pulsar «Cortar audio» se abre un modal de casi toda la pantalla que guía en **4 pasos numerados**, desde subir el audio hasta descargar el resultado, sin salir del modal.

| Paso | Título en pantalla | El usuario… | Se habilita cuando |
|---|---|---|---|
| 1 | Sube el audio | escribe su nombre (solo en el sitio público) y elige o arrastra el archivo | siempre |
| 2 | Marca los fragmentos | arrastra sobre la onda, escucha y ajusta tiempos | el audio está analizado |
| 3 | Elige qué hacer | conservar, quitar o silenciar; opciones avanzadas | hay al menos 1 fragmento |
| 4 | Corta y descarga | revisa el resumen, corta, ve el progreso y descarga | los pasos 1 a 3 son válidos |

---

## 2) Decisiones (no cambiar sin el usuario)

- **El modal se abre al elegir la herramienta.** Pulsar la tarjeta «Cortar audio» la selecciona y abre el modal. `?herramienta=audiocut` también lo abre al cargar la página. Si se cierra, el área del formulario muestra una tarjeta con «Abrir el cortador».
- **`<dialog>` nativo con `showModal()`**. Da gratis el foco atrapado, el fondo inerte, la capa superior y Escape.
- **Sin modo oscuro**: el sitio no tiene. La «mesa de edición» oscura es una superficie fija, no un tema.
- **Vocabulario: «fragmento»** en lugar de «región» en toda la interfaz y en los mensajes del servidor. Las claves técnicas (`segments`, `spans`) no cambian.
- **Sin librerías nuevas de animación.** Se usan `chat-motion` y Alpine (`x-transition`). La única fuente nueva es Red Hat Mono, y solo para cifras.

---

## 3) Dirección visual (`frontend-design`)

### Concepto

La **mesa de edición de una sala de audiencias**. El modal es claro, tranquilo e institucional, como el resto de 4toTOP. En el paso 2, la onda vive en una banda oscura con un reloj de cifras grandes, como el panel de la grabadora de la sala. Debajo está la pieza que el usuario recordará: la tira **«Así quedará tu audio»**, que se recompone en vivo al marcar fragmentos o cambiar de modo y muestra la duración final.

### Tokens

| Nombre | Hex | Uso |
|---|---|---|
| Toga | `#0A2E5D` | Marca. Acciones principales, paso activo y encabezado del modal (blanco sobre Toga: 13,45:1) |
| Mesa | `#0B1B33` | Fondo de la mesa de edición (paso 2) |
| Señal | `#7DD3FC` | Barras de la onda y texto secundario sobre Mesa (10,33:1) |
| Cabezal | `#FBBF24` | Cursor de reproducción y reloj grande sobre Mesa (10,32:1). Es el único color cálido de la interfaz |
| Papel | `#F5F8FC` | Superficies claras del modal |
| Conserva | `#15803D` | Lo que queda (4,71:1 sobre Papel) |
| Quita | `#B91C1C` | Lo que se quita; con rayado diagonal, para no depender solo del color (6,07:1 sobre Papel) |
| Silencio | `#475569` | Lo que se silencia; con puntos (7,11:1 sobre Papel) |
| Tinta tenue | `#94A3B8` | Texto auxiliar sobre Mesa (6,72:1) |

Contrastes medidos con el verificador WCAG de antislop: todos pasan AA para texto normal. Los rellenos de fragmentos sobre la onda usan esos colores al 28 % de opacidad y un borde del color pleno.

### Tipografía

| Rol | Fuente | Uso |
|---|---|---|
| Texto y títulos | La del sitio (`font-sans` del layout) | Consistencia con 4toTOP; títulos de paso en 600 |
| Cifras | **Red Hat Mono** 500/600 (`fonts.bunny.net`, ya usado por el proyecto) | Reloj grande (clamp 28–44 px), números de paso, tiempos de fragmentos y duración final. Siempre `font-variant-numeric: tabular-nums` |

Escala: título del modal 20/28; título de paso 18/26; cuerpo 15/22; ayuda 13/18; reloj grande `clamp(1.75rem, 3.2vw, 2.75rem)`.

### Disposición

Escritorio (≥ 1024 px): modal con 16 px de margen alrededor (`inset: 1rem`), radio de 16 px y sombra amplia.

```
┌────────────────────────────────────────────────────────────────────────────┐
│ Cortar audio                                              [×] Cerrar        │
│ ①━━━━━━━━━━━②━━━━━━━━━━━③━━━━━━━━━━━④                                       │
│ Sube        Marca         Elige         Corta y                             │
│ audio-701   3 fragmentos  Quitar lo     descarga                            │
│ · 58:12                   marcado                                           │
├────────────────────────────────────────────────────────────────────────────┤
│  (contenido del paso activo; ver wireframes por paso)                      │
├────────────────────────────────────────────────────────────────────────────┤
│ [← Atrás]                       Paso 2 de 4           [Siguiente: Elige →] │
└────────────────────────────────────────────────────────────────────────────┘
```

Celular (< 640 px): ocupa toda la pantalla (`inset: 0`, sin radio). El riel se reduce a «Paso 2 de 4 · Marca los fragmentos» con una barra de progreso, y el pie con Atrás/Siguiente queda fijo abajo, respetando `env(safe-area-inset-bottom)`.

**Paso 1: Sube el audio**

```
  ┌───────────────────────────────────────────────┐
  │  Nombre de quien realiza el corte  [________] │   (solo en el sitio público)
  │  ┌─────────────────────────────────────────┐  │
  │  │   ♪  Arrastra el audio aquí            │  │   zona de soltar = <label> del input
  │  │      o elige un archivo                 │  │
  │  │   MP3, M4A, WAV… · hasta 50 MB y 4 horas│  │
  │  └─────────────────────────────────────────┘  │
  │  ▓▓▓▓▓▓▓▓▓░░░░░  Subiendo… 62 %               │   progreso real de Livewire
  │  Analizando el audio…  (brillo cm-shimmer)    │
  └───────────────────────────────────────────────┘
```

Al terminar el análisis aparece la ficha del archivo (nombre, duración y formato) y el modal **avanza solo al paso 2** tras 400 ms. El usuario no tiene que buscar un botón.

**Paso 2: Marca los fragmentos** (el elemento distintivo)

```
  ┌ MESA ───────────────────────────────────────────────────────────────┐
  │  01:12.480  / 58:12.017            [▶] [▶ fragmento] [+] [✂] [↶][↷] │  reloj Cabezal, Red Hat Mono
  │  ┊0:00      ┊10:00     ┊20:00     ┊30:00     ┊40:00     ┊50:00      │  regla de tiempo
  │  ▁▃▅▇▅▃▁▃▆▇█▆▃▁▂▅▇▆▅▃▂▁▃▅▇▅▃▁▃▆██▆▃▁▂▅▇▆▅▃▂▁▃▅▇▅▃▁▃▆▇█▆▃▁           │  onda Señal; fragmentos encima
  │        [███ 1 ███]          [██ 2 ██]                                │
  │  Arrastra sobre la onda para marcar un fragmento.  [−] zoom [+]     │
  └─────────────────────────────────────────────────────────────────────┘
  Así quedará tu audio                                   Duración final 41:07.250
  [███████ conserva ███████][▨ 1 ▨][██████ conserva ██████][▨ 2 ▨][██████]
  ┌ Fragmentos (2) ──────────────────────────────────────────────────────┐
  │ 1  Inicio [ 1:12.480 ]  Fin [ 9:40.000 ]  8:27.520   [▶] [🗑]         │
  │ 2  Inicio [22:05.000 ]  Fin [30:51.200 ]  8:46.200   [▶] [🗑]         │
  └──────────────────────────────────────────────────────────────────────┘
```

- La **regla de tiempo** es nueva: marcas cada 1, 5, 10 o 30 min según la duración y el zoom. Usa el plugin `Timeline` de wavesurfer 7.12.12, que ya está instalado, con estilos propios.
- **«Así quedará tu audio»** es una tira proporcional a la duración original. Pinta cada tramo según el modo: Conserva, Quita (rayado) o Silencio (puntos). La duración final se calcula con la misma regla del servidor (ver el paso 4 del plan).
- Sin fragmentos marcados, la mesa muestra dentro de la onda un estado vacío con instrucción: «Arrastra sobre la onda para marcar el primer fragmento», con una mano/flecha animada **una sola vez**.

**Paso 3: Elige qué hacer**

```
  ┌───────────────────────┐ ┌───────────────────────┐ ┌───────────────────────┐
  │ ██▨▨██▨▨██  →  ▨▨▨▨   │ │ ██▨▨██▨▨██  →  ██████ │ │ ██▨▨██▨▨██  → ██··██··│
  │ Quedarme solo con     │ │ Quitar lo marcado     │ │ Silenciar lo marcado  │
  │ lo marcado            │ │ y unir el resto       │ │ (misma duración)      │
  │ Final: 17:13.720      │ │ Final: 40:58.297      │ │ Final: 58:12.017      │
  └───────────────────────┘ └───────────────────────┘ └───────────────────────┘
  ☐ Guardar cada fragmento en un archivo aparte (solo «Quedarme…»; ZIP si son varios)
  ▸ Opciones avanzadas   (fundido · formato de salida · normalizar volumen)
  Así quedará tu audio  [la misma tira del paso 2, ahora con el modo elegido]
```

- Las tres tarjetas son **radios** (`role="radiogroup"`) y cada una tiene un diagrama antes→después hecho con la misma tira. Cada tarjeta muestra la duración final que tendría su modo, así el usuario elige viendo la consecuencia.
- «Opciones avanzadas» es un `<details>`, cerrado por defecto: fundido, formato (MP3 por defecto) y normalizar.

**Paso 4: Corta y descarga**

```
  Resumen
  audio-701.mp3 · 58:12.017  →  audio-701_recortado.mp3 · 40:58.297
  Quitar 2 fragmentos · sin fundido · MP3
                                       [ Cortar audio ]
  ── después de pulsar ──
  ◌ En espera… → ◌ Cortando el audio…   (barra indeterminada)
  ✓ Listo. Se quitaron 17:13.720 de audio.
  [ Descargar MP3 ]   [ Cortar otro audio ]   [ Volver a marcar este audio ]
```

### Crítica aplicada (qué se cambió del primer boceto y por qué)

1. **El riel de pasos** era un stepper genérico de círculos y líneas. Ahora cada paso completado muestra la decisión tomada (archivo y duración, cantidad de fragmentos, modo). Funciona como resumen navegable: un clic vuelve a ese paso.
2. **El selector de modo** eran tres botones de texto. Ahora son tarjetas con un diagrama antes→después y la duración resultante: el usuario no tiene que imaginar qué significa «Silenciar».
3. **El encabezado** tenía un degradado decorativo; se quitó. El único acento cálido es Cabezal (cursor y reloj), porque es lo que el ojo debe seguir.
4. **El color de los fragmentos** dependía solo del verde o el rojo. Se agregaron rayado y puntos, que también se distinguen sin percepción de color.
5. **Se quitó un accesorio**, según la regla de Chanel de la skill: el contador animado de «fragmentos marcados» del encabezado. La lista ya lo muestra.

---

## 4) Movimiento (`chat-motion`)

Tokens de `chat-motion.css`: `--cm-t-fast` 160 ms, `--cm-t-base` 260 ms, `--cm-t-slow` 420 ms, `--cm-stagger` 70 ms, `--cm-ease-out` y `--cm-ease-spring`. Reglas que no se negocian:

- Solo se animan `transform` y `opacity`, más `box-shadow` en hover.
- Las salidas son más rápidas que las entradas.
- Lo que el usuario puede revertir usa transiciones CSS, no keyframes.
- El *spring* solo se usa en cosas pequeñas que llegan.
- Nada supera 560 ms.
- `prefers-reduced-motion` deja todo en 1 ms (ya viene en el CSS).

| # | Momento | Implementación | Tiempo |
|---|---|---|---|
| M1 | Abrir modal | `::backdrop` fundido; panel `opacity 0→1` + `translateY(16px) scale(.985)→none` | 420 ms entrada / 180 ms salida |
| M2 | Contenido del paso al abrir | Hijos del paso con `.cm-rise` y `style="--cm-i: n"` (escalonado de 70 ms) | 260 ms + escalón |
| M3 | Cambiar de paso | `withViewTransition()` con `view-transition-name: ac-stage` y dirección: adelante, lo nuevo entra desde `translateX(24px)` y lo viejo sale a `-24px`; atrás, al revés (clase `is-back` en `<html>` durante la transición) | 320 ms entrada / 180 ms salida |
| M4 | Riel de pasos | La línea entre pasos completados es un `scaleX(0→1)` con origen a la izquierda. El número pasa a ✓ con `--cm-ease-spring` (patrón del check de `.cm-chip`) | 420 ms / 260 ms |
| M5 | Zona de soltar | En `dragover`, `.cm-lift` y borde Toga; al soltar, `.cm-press` | 160 ms |
| M6 | Subida | Barra `transform: scaleX(progreso)` con el evento `livewire-upload-progress` | transición de 260 ms |
| M7 | Análisis | Texto con `.cm-shimmer` («Analizando el audio…») | ciclo de 1,4 s mientras dura |
| M8 | Onda lista | El contenedor de la onda entra con `opacity` + `scaleY(.6→1)` desde el centro | 420 ms |
| M9 | Fragmento nuevo en la lista | `.cm-rise`; al quitarlo, salida con `opacity` + `translateX(-8px)` en 180 ms antes de borrarlo del arreglo | 260 / 180 ms |
| M10 | Contador «Fragmentos (n)» y duración final | `bump()` de chat-motion cuando cambian | 260 ms, spring |
| M11 | Tira «Así quedará» | Cada tramo es un `div` con `transform: translateX() scaleX()` calculado; al cambiar modo o fragmentos se interpola por transición | 420 ms |
| M12 | Elegir modo | Tarjeta activa: `.cm-press` + anillo; el diagrama interno reproduce una vez el «antes→después» (el tramo marcado se desvanece o se va al centro) | 420 ms |
| M13 | Cortar | El botón cambia a «Cortando…» con un fundido cruzado de etiquetas (patrón enviar↔detener de chat-motion) | 260 ms |
| M14 | Progreso | Barra indeterminada: un segmento que se desliza con `translateX` en bucle (solo mientras está en espera o procesando) | 1,2 s por ciclo |
| M15 | Listo | ✓ con spring y el botón «Descargar» con `.cm-rise` | 260 / 420 ms |

Con movimiento reducido, `withViewTransition` cambia directo y la tira se actualiza sin interpolar. Firefox sin View Transitions también cambia directo, y nada se rompe.

---

## 5) Arquitectura

### Archivos

| Acción | Archivo | Qué |
|---|---|---|
| Copiar tal cual | `resources/css/chat-motion.css`, `resources/js/lib/chat-motion.js` | Desde `~/.claude/skills/chat-motion/assets/`. **No reescribirlos** |
| Crear | `resources/css/audio-cutter.css` | Clases `ac-*`: modal, riel, mesa, tira, tarjetas de modo, rayados (`repeating-linear-gradient`) y view transitions `ac-stage`. Mapea `--cm-accent: #0A2E5D` y `--cm-accent-soft: #E0EAF6` dentro de `.ac-modal` |
| Modificar | `resources/css/app.css` | `@import './chat-motion.css'; @import './audio-cutter.css';` después de Bootstrap y antes de las capas de Tailwind (ver `AGENTS.md`) |
| Crear | `resources/views/livewire/partials/audio-cutter-modal.blade.php` | El `<dialog>` completo, con los 4 pasos |
| Reemplazar | `resources/views/livewire/partials/conversion-tools/audiocut.blade.php` | Tarjeta lanzadora: «Corta un trozo de una grabación en 4 pasos» y botón «Abrir el cortador» |
| Modificar | `resources/views/livewire/file-converter.blade.php` | Para `audiocut`: ocultar nombre, archivo y botón «Convertir» del formulario general (ahora viven en el modal); incluir el modal una vez; arreglar las etiquetas de descarga (ver más abajo) |
| Modificar | `resources/js/audio-editor.js` | Estado de pasos y apertura en `createCutter`, `previewSpans()`, regla de tiempo, tira de resultado |
| Modificar | `resources/js/app.js` | `window.audioCutter` sin cambios de contrato; importar `withViewTransition` y `bump` desde `./lib/chat-motion` dentro de `audio-editor.js` (carga diferida, como hoy) |
| Modificar | `app/Livewire/Concerns/ConvertsFiles.php` | `public ?string $lastToken`, `$lastJob` en `render()`, `startOver()` e intervalo de *poll* |
| Modificar | `app/Conversions/AudioCutTool.php` | Textos: «región» → «fragmento» |
| Modificar | `resources/views/layouts/in.blade.php` y `layouts/app.blade.php` | `<link>` a Red Hat Mono en `fonts.bunny.net`, con `display=swap` |
| Modificar | `tests/Feature/AudioCutTest.php` | Mensajes nuevos y pruebas del modal (sección 7) |
| Modificar | `docs/file-conversions.md` | Describir el flujo de 4 pasos |

### Trampas conocidas (léelas antes de escribir código)

1. **`wire:poll.5s` en la raíz del componente.** Cada morph de Livewire reescribe los atributos según el HTML del servidor, y borraría el atributo `open` que pone `showModal()`: el modal se cerraría solo cada 5 s. Pon **`wire:ignore.self`** en el `<dialog>`: Livewire actualiza sus hijos pero no toca sus atributos. Verifícalo dejando el modal abierto 15 s (sección 7).
2. **Contenido que no debe re-renderizar**: la mesa (onda y regla) y la tira «Así quedará» ya están en `wire:ignore`; mantenlo. Los campos de Livewire (nombre, archivo, modo, opciones) **no** van en `wire:ignore`.
3. **SweetAlert2 no se ve sobre un `<dialog>` modal**: el diálogo está en la *top layer* y Swal se agrega al `body`, por debajo. La confirmación al cerrar con fragmentos marcados («¿Cerrar el cortador? Se perderán los fragmentos marcados.») va **dentro** del diálogo, como un panel propio con «Seguir editando» (foco por defecto) y «Cerrar y descartar».
4. **Escape**: el evento `cancel` del diálogo llama a `preventDefault()` si hay fragmentos marcados o un corte en curso, y muestra esa confirmación. Además, los atajos del editor (Espacio, Supr…) siguen limitados a la mesa con foco; Escape dentro de un campo de tiempo solo deshace la edición del campo.
5. **El input de archivo**: el JS escucha `change` en `#conversion-file`. Ese input pasa a vivir dentro del modal, conservando el `id` y `wire:model="file"`. Solo puede existir una vez en la página: el formulario general no lo renderiza para `audiocut`.
6. **Etiqueta de descarga**: hoy el mapa en `file-converter.blade.php` solo conoce `docx/mp3/pdf/zip`, así que un WAV dice «Descargar archivo». Agrega `wav` → `WAV`, `m4a` → `M4A`, `ogg` → `OGG` y `flac` → `FLAC`. Usa el mismo mapa en el paso 4 del modal: extráelo a un método `ConversionTool::downloadLabel(string $extension)` o a un `@php` compartido.

### Estado y flujo

**Alpine**, en `createCutter`: `isOpen`, `step` (1–4), `furthest` (el paso más lejano alcanzado), `direction`, `confirmClose`, y los ya existentes `regions`, `duration`, `playable`…

- `open()` llama a `this.$refs.dialog.showModal()`, fija `step = file ? (regions.length ? 3 : 2) : 1` y enfoca el título del paso.
- `close(force)`: si hay fragmentos o corte en curso y no es `force`, muestra la confirmación; si no, cierra el diálogo con la animación de salida (M1) y llama a `load(null)` solo si no hay un resultado listo.
- `go(n)`: solo avanza si `canGo(n)`. Ejecuta `withViewTransition(() => { this.direction = …; this.step = n; })`, mueve el foco al `<h3>` del paso y anuncia «Paso n de 4: título» en una región `aria-live="polite"`.
- `canGo(2) = hasEditor`; `canGo(3) = regions.length > 0`; `canGo(4) = canGo(3)`.
- Al llegar los picos (`audio-loaded`) con `step === 1`, se ejecuta `go(2)` tras 400 ms.
- El evento `conversion-queued` ya no limpia el editor: marca `submitted = true` y se queda en el paso 4.
- `previewSpans(mode)` replica en JS la regla de `AudioCutTool::spans()`: redondeo, unión de solapados y complemento con un mínimo de 0,01 s. Devuelve `{ segments: [{ start, end, kind: 'keep'|'cut'|'mute' }], final }`, donde `final` resta `(n − 1) × fade` cuando corresponde. **El número que muestra el modal tiene que coincidir con el aviso del servidor** (prueba 7.B.5).

**Livewire**, en `ConvertsFiles`:

- `public ?string $lastToken = null;`. `convert()` lo fija con el `token` de la conversión creada; `FileConversionService::create()` ya la devuelve.
- `render()` pasa `$lastJob` (la conversión de `lastToken` de esta sesión o de este usuario, con el mismo filtro que `$jobs`).
- `startOver()` limpia `lastToken`, `file`, `audioDuration` y las opciones (`defaults()`) y despacha `audio-cutter-reset`. Alpine vuelve al paso 1.
- Ajuste **opcional** del *poll*: con `$lastJob` en `queued` o `processing`, la raíz usa `wire:poll.2s`; si no, `wire:poll.5s`. Livewire evalúa el atributo en cada render.
- «Volver a marcar este audio» (tras el paso 4): limpia `lastToken` y vuelve al paso 2 conservando archivo y fragmentos. El archivo temporal de Livewire sigue disponible. Si falla la validación de `file` (subida temporal vencida), muestra el error en el paso 1.

### Accesibilidad

- `<dialog aria-labelledby="ac-title" aria-describedby="ac-step-live">`.
- El riel es un `<ol>`: el paso activo lleva `aria-current="step"`; los pasos bloqueados son `<button disabled>` con el motivo en `title` y en texto `sr-only`.
- Al cambiar de paso, el foco va al `<h3 tabindex="-1">` del paso. Al cerrar, vuelve a la tarjeta «Cortar audio».
- Las tarjetas de modo son `<input type="radio">` reales, ocultos visualmente, dentro de un `<label>` por tarjeta.
- La zona de soltar es el `<label for="conversion-file">`, así que funciona con teclado (Enter o Espacio abren el selector).
- Los objetivos táctiles miden al menos 44 px y el foco visible es `.cm-focus-ring` o `outline` Toga de 2 px.
- La tira de resultado es decorativa (`aria-hidden="true"`); su contenido se dice en texto: «Duración final: 40:58.297».

---

## 6) Paso a paso

1. **Base**:
   - Copia `chat-motion.css` y `chat-motion.js` tal cual.
   - Crea `audio-cutter.css` con los tokens de la sección 3.
   - Agrega los `@import` y la fuente.
   - Verifica: `npm run build`, y la página sin cambios visibles fuera del cortador.
2. **Modal vacío con riel y pie**:
   - Crea el `<dialog>` con `wire:ignore.self`, la apertura desde la tarjeta y la URL, el cierre con confirmación interna, y M1, M3 y M4.
   - Verifica: se abre, cambia de paso sin contenido, Escape funciona y sigue abierto tras 15 s de *poll*.
3. **Paso 1**:
   - Mueve nombre y archivo al modal, con la zona de soltar (M5), la barra de progreso (M6), el texto de análisis (M7), la ficha del archivo y el avance automático.
   - Reemplaza la tarjeta lanzadora y oculta esos campos del formulario general para `audiocut`.
4. **Paso 2**:
   - Mueve la mesa: estilos Mesa/Señal/Cabezal, reloj grande, regla con `TimelinePlugin` (`wavesurfer.js/dist/plugins/timeline.esm.js`), estado vacío, lista de fragmentos (M9, M10) y la tira «Así quedará» (M11) con `previewSpans`.
   - Los colores de fragmento siguen al modo (hoy lo hace `recolor()`).
5. **Paso 3**: tarjetas de modo con diagrama y duración (M12), «Guardar cada fragmento aparte», y «Opciones avanzadas» con fundido, formato y normalizar.
6. **Paso 4**:
   - Resumen, botón «Cortar audio» (M13), y estado del trabajo desde `$lastJob` (M14, M15).
   - Muestra la descarga, «Cortar otro audio» (`startOver`) y «Volver a marcar este audio».
   - Muestra el error del trabajo si falla, con «Volver al paso 3».
7. **Textos del servidor**: «región» → «fragmento» en `AudioCutTool` y en los mensajes de `ConvertsFiles`. Actualiza los tests que comparan esos textos.
8. **Etiquetas de descarga** (trampa 6) y **documentación** (`docs/file-conversions.md`, sección «Cortar audio»: flujo de 4 pasos).
9. **Verificación** completa de la sección 7 y commits por paso en la rama `ux-cortar-audio`.

---

## 7) Verificación

### A. Pest (`tests/Feature/AudioCutTest.php`)

1. La página con `?herramienta=audiocut` incluye el diálogo (`assertSee('id="audio-cutter"', false)`) y los 4 títulos de paso. El formulario general **no** renderiza `id="conversion-file"` dos veces (cuenta las apariciones).
2. Con otra herramienta (`pdf`), el diálogo no se renderiza.
3. Al convertir, se fija `lastToken`; `render()` expone `lastJob`, y la vista muestra «En espera» en el paso 4.
4. `startOver()` deja `lastToken`, `file` y `audioDuration` en `null` y las opciones en `defaults()`, y despacha `audio-cutter-reset`.
5. Los mensajes dicen «fragmento»: actualiza los datasets de `spans()`.
6. Etiqueta de descarga: un trabajo listo con `result_name` `.wav` muestra «Descargar WAV».
7. Los tests existentes de audio, PDF y conversiones siguen pasando.

### B. Navegador (Playwright, servidor del worktree en `:8012`)

1. Clic en la tarjeta «Cortar audio» → el modal se abre con M1 y el foco en «Sube el audio».
2. Subir el MP3 de 5 min → barra de progreso, brillo de análisis y **avance solo al paso 2**.
3. Arrastrar dos fragmentos → la lista los agrega (M9), el contador hace `bump` y la tira muestra los tramos.
4. Paso 3: cambiar entre los tres modos → la tira y la duración se interpolan y cada tarjeta muestra su duración.
5. Paso 4 con «Quitar lo marcado» → «Cortando…», luego ✓ y «Descargar MP3». La duración del archivo descargado coincide con «Duración final» del paso 3 (±0,15 s) y con el aviso del servidor.
6. Dejar el modal abierto **15 s** → no se cierra ni pierde la onda ni los fragmentos (trampa 1).
7. Escape con fragmentos → confirmación interna; «Seguir editando» la cierra y conserva todo.
8. Solo teclado: Tab por el riel, la zona de soltar, la mesa (atajos), las tarjetas de modo (flechas en el radiogroup) y Cortar.
9. `page.emulateMedia({ reducedMotion: 'reduce' })` → todo funciona y los cambios son instantáneos.
10. Firefox: los cambios de paso son directos, sin errores en la consola.
11. 390 × 844: el modal ocupa toda la pantalla, el riel compacto, el pie fijo y sin desplazamiento horizontal. Captura cada paso.
12. 1280 × 900: captura de cada paso; compara contra los wireframes de la sección 3.
13. Contraste: vuelve a comprobar cualquier color nuevo con `mcp__plugin_antislop_antislop-contrast__check_contrast`.

### C. Comandos

`./vendor/bin/pint --test`, `./vendor/bin/pest tests/Feature/AudioCutTest.php tests/Feature/PdfToolsTest.php tests/Feature/FileConversionTest.php` y `npm run build`. El chunk `audio-editor` puede crecer por la regla de tiempo y la tira, pero debe seguir siendo diferido: `app-*.js` no crece más de unos 3 KB.

---

## 8) Criterios de aceptación

- [ ] Pulsar «Cortar audio» abre un modal de casi toda la pantalla con 4 pasos numerados; en celular ocupa la pantalla entera.
- [ ] Cada paso se habilita solo cuando el anterior está listo, y el riel muestra la decisión tomada en cada paso completado.
- [ ] La tira «Así quedará tu audio» y las duraciones de las tarjetas coinciden con el resultado real (±0,15 s).
- [ ] Se descarga sin salir del modal y se puede empezar otro corte o volver a marcar el mismo audio.
- [ ] El modal sobrevive al *poll* de Livewire, y Escape no hace perder trabajo sin confirmar.
- [ ] Las animaciones siguen la tabla de la sección 4, con `prefers-reduced-motion` respetado y nada por encima de 560 ms.
- [ ] Contraste AA, foco visible, uso completo con teclado y objetivos táctiles de 44 px o más.
- [ ] Pint, Pest y build pasan; los assets de `chat-motion` están copiados sin modificar.

## 9) Fuera de alcance

Cambios en la lógica de corte o en los formatos, edición multipista, guardar proyectos, modo oscuro del sitio, rediseñar las demás herramientas del conversor, y los patrones de chat de `chat-motion` (streaming, citas, historial).

## 10) Dónde lo construido difiere de este plan

Donde haya contradicción, manda esta sección. Todo lo demás se implementó como está escrito.

| Plan | Construido | Por qué |
|---|---|---|
| Escape y «×» piden confirmación si hay fragmentos o un corte en curso (trampas 3 y 4, `close(force)`, prueba 7.B.7) | Cerrar **conserva el avance** y no pide confirmación; «Abrir el cortador» retoma donde quedó | No se pierde nada al cerrar, así que confirmar solo estorbaría. El criterio «Escape no hace perder trabajo sin confirmar» se cumple porque no hay trabajo que perder |
| `startOver()` despacha `audio-cutter-reset` | `startOver()` y `editAgain()` los llama Alpine y esperan la respuesta de Livewire; no hay evento | Esos botones desaparecen con el re-render, y el cambio de paso (y su foco) tiene que ocurrir después |
| Buscar el título del paso y el diálogo con `$refs` | `dialogEl()` y `heading()` los buscan por id en el documento | Justo después de un re-render de Livewire, `$refs` y `$root` de Alpine vuelven vacíos; el foco falló en «Volver a marcar» y «Cortar otro audio» hasta cambiarlo |
| «Opciones avanzadas» es un `<details>` normal | Lleva `wire:ignore.self`, y `submit()` lo abre si un error de validación está dentro | El `wire:poll.5s` de la raíz lo cerraba cada 5 segundos |
| Cambio de paso con `withViewTransition()` (M3) | `x-transition` de Alpine con la dirección en `--ac-dir` | Más simple, y la entrada y la salida usan las duraciones del plan (320 ms y 180 ms) |
| `poll` de 2 s según el estado del trabajo en la raíz | La raíz conserva `wire:poll.5s`; mientras hay un trabajo pendiente, el paso 4 agrega su propio `wire:poll.2s` | No hizo falta tocar la raíz |
| Mensajes de validación de `ConvertsFiles` sin cambios | El campo `actorName` se llama «nombre» en los mensajes (`El campo nombre es obligatorio.`) | Antes decía «actor name» |

Sin verificar en el navegador: Firefox (no hay uno disponible en el entorno de pruebas).
