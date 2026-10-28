# Plan: tablas de vidrio en el front office público

Ejecuta: **Codex (`gpt-6.1-terra`)**, una tarea por vista.
Orquesta y valida: **Claude Code**, que revisa cada entrega antes de lanzar la siguiente.

## Objetivo

Rediseñar las vistas públicas con tablas para que **sigan siendo tablas**, pero de vidrio: el fondo
del Centro de Justicia (`.fo-backdrop`) debe verse a través de la tabla sin perder legibilidad.

| Tarea | Vista pública | Archivos principales |
|---|---|---|
| T1 | `/guiastelefonicas` | `resources/views/guiastelefonicas.blade.php`, `app/Livewire/TablaGuias.php` (solo `render()`), vista Livewire nueva para modo lectura |
| T2 | `/ausencias` | `resources/views/ausencias.blade.php`, `resources/views/livewire/ausentismo-calendario.blade.php` |
| T3 | `/convertir-archivos` | `resources/views/livewire/file-converter.blade.php` (compartida con backoffice) |
| T4 | `/ttypp` | `resources/views/ttypp-welcome.blade.php` |
| T0 | Base común (la hace Claude antes de T1) | `resources/css/front-office.css`, `resources/views/components/public/page-head.blade.php` |

## Referencias que ya existen (reutilizar, no duplicar)

- Tabla de vidrio ya hecha: `resources/views/livewire/buscador-sentencias-tabla.blade.php`
  (`.fo-table-wrap`, `.fo-table`, `.fo-sort` con `aria-sort`, filas desplegables, tarjetas en móvil).
- Paginación: `resources/views/components/public/pagination.blade.php` → `$paginator->links('components.public.pagination')`.
- Cabecera y barra de filtro: `resources/views/components/public/list-page.blade.php` (`.fo-page-head`, `.fo-listbar`, `.fo-chips`).
- Tokens de color, vidrio y movimiento: bloque `.fo { … }` al inicio de `resources/css/front-office.css`.
- Transición "abrir app": `.claude/skills/4totop-app-open-transition/SKILL.md`.

## T0 — Base común (Claude, antes de delegar)

1. Variante `.fo-table-wrap--glass`: usa `--fo-glass` (más transparente que `--fo-glass-strong`), filas sin fondo propio,
   encabezado `position: sticky` con vidrio fuerte, separadores con `--fo-line`, hover con `--fo-glass-hover`.
2. Componente `<x-public.page-head title lead meta>` extraído de `list-page` (link `‹ Inicio` + título + bajada) para no repetirlo.
3. Clase `.fo-dash` para celdas vacías (muestra `—` atenuado en vez de `-`).
4. Snapshot del HTML que hoy renderizan `/guias` (backoffice) y `/backoffice/convertir-archivos`, para comparar al final.

## Reglas para todas las tareas (Codex)

**No tocar**
- Lógica PHP, consultas, validaciones, rutas, permisos (Spatie), controladores (no renombrar los legacy en minúsculas).
- El backoffice: `/guias` (TablaGuias modo `full`, `layouts.app`) y `/backoffice/convertir-archivos` deben verse **idénticos**.
- `resources/js/lib/app-open.js`, `app-open.css`, `layouts/in.blade.php`, `components/public/layout.blade.php`.
- No agregar dependencias npm/composer. Vite sigue construyendo solo `app.css` y `app.js`.

**Cómo aislar los componentes compartidos**
- T1: en `TablaGuias::render()` elegir una vista nueva `livewire.tabla-guias-publica` cuando `$modo === 'lectura'`;
  la vista actual queda intacta para el backoffice.
- T3: el backoffice no tiene `body.fo`. Agregar clases `fo-*` como ganchos y estilarlas **solo** bajo `.fo` en `front-office.css`.
  Si una clase existente choca, sobreescribirla con selector `.fo .clase`, nunca editarla para todos.

**Estilo**
- Todo CSS nuevo en `resources/css/front-office.css`, en una sección con título por vista, con selectores que empiecen por `.fo `.
- Solo tokens (`var(--fo-*)`, `var(--cm-*)`); ningún color hex nuevo. Debe verse bien en tema oscuro y claro (`:root[data-theme="light"]`).
- Tipografía: títulos con `--fo-display`, datos/códigos (RIT, RUC, RUT, teléfonos, fechas) con `.fo-num` (mono tabular).
- Quitar clases `animate__*`, el botón legacy "INICIO" absoluto, emojis y SVG copiados de SVGRepo; usar FontAwesome (ya cargado).
- Cabecera con `<x-public.page-head>`; el contenido en **2–4 bloques hijos directos** de `[data-app-view]` (entrada escalonada).
- Nada de animaciones en bucle; respetar `prefers-reduced-motion` (ya cubierto por el bloque global si se usan clases `.fo`).

**Accesibilidad y responsive**
- `<table>` real con `<caption class="sr-only">`, `<th scope="col">`; columnas ordenables con `<button>` dentro del `th` y `aria-sort`.
- Contraste de texto ≥ 4.5:1 sobre el vidrio en ambos temas. Foco visible (`:focus-visible` ya global).
- Móvil (375 px) sin scroll horizontal de la página: la tabla pasa a tarjetas (patrón de sentencias) o hace scroll **dentro** de su contenedor con la primera columna fija.
- Estados vacíos que digan qué pasa y qué hacer (ej. "No hay registros TTyPP para el 28-10-2026. Prueba con otro día.").

## Especificación por vista

### T1 — Guías telefónicas
- Barra: búsqueda (la existente), filtro de institución como chips si son ≤ 8, si no `select.fo-select`; "Mostrar N".
- Tabla: Nombre (negrita) · Institución · Teléfonos (`tel:` clicables, mono) · Email (`mailto:`) · RUT (mono, atenuado).
- Sin acciones de edición en modo lectura. Paginación propia.

### T2 — Ausencias (calendario)
- Mantener calendario mensual como `<table>`: encabezado de días, celdas de vidrio, hoy resaltado con `--fo-signal`,
  días con ausencias muestran conteo (`.fo-badge--num`), fines de semana atenuados.
- Navegación de mes: botones `‹` `›` y título del mes con `--fo-display`.
- Modal del día → panel de vidrio (puede reutilizar estilos `.fo-drawer`), lista de personas ausentes y motivo.

### T3 — Convertir archivos
- Solo apariencia en el front office: tarjetas de herramienta, zonas de carga y tablas (historial/resultados) en vidrio.
- No cambiar `wire:*`, nombres de campos, textos que validan los tests (ej. "Nombre de quien realiza la conversión").

### T4 — TTyPP
- Barra de fecha: `‹ Anterior` · `<input type="date">` · `Hoy` · `Siguiente ›` (mismo GET actual), fecha legible en la cabecera.
- Tabla de 12 columnas en vidrio con encabezado sticky; RIT/RUC mono; `-` → `.fo-dash`.
- Escritorio: columnas secundarias (Encargadas TI MP/D/Zoom, Acta, Jueces) agrupables en fila desplegable si no caben.
- Móvil: tarjetas.

## Entrega de cada tarea (Codex)

1. Cambios solo en los archivos de la tarea + su sección en `front-office.css` (+ tests).
2. Test Pest de la vista (render, datos visibles, estado vacío) en `tests/Feature/FrontOfficeTablesTest.php`.
3. Correr: `./vendor/bin/pint --test` sobre los PHP tocados, el Pest de la tarea y `npm run build`.
4. Resumen final: archivos tocados, decisiones, lo que no se pudo hacer.

## Validación (Claude, después de cada tarea)

1. **Alcance:** `git diff --stat` solo con archivos permitidos; nada en backoffice, rutas, permisos ni PHP de lógica.
2. **Backoffice intacto:** HTML de `/guias` y `/backoffice/convertir-archivos` igual al snapshot de T0.
3. **Tests:** Pest de la tarea + `FrontOffice*`, `TtyppPageTest`, `FileConversionTest`, `PdfToolsTest`, `AudioCutTest`, `Security`; `pint --test`; `npm run build`.
4. **Visual:** capturas headless 1440 px y 375 px, tema oscuro y claro; sin scroll horizontal ni errores de consola; el fondo se ve a través de la tabla.
5. **Transición:** abrir desde la tarjeta de la portada y volver; sin `.ao-layer` residual.
6. Si algo falla: Claude devuelve a Codex la lista concreta de correcciones (misma sesión) o corrige directamente si es menor.

## Orden y paralelismo

T0 → T4 (TTyPP, la más simple, valida el patrón) → T1 → T2 → T3.
Secuencial: todas tocan `front-office.css`, y en paralelo chocarían.
