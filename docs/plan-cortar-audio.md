# Plan: cortar audio en /convertir-archivos, al estilo de Audacity

Fecha: 26 de octubre de 2026. Origen: `~/Descargas/audacity/` (`audacity-win-2.0.4.exe` y `Lame_v3.99.3_for_Windows.exe`).

> La orden de trabajo detallada está en [Implementacion_CortarAudios.md](Implementacion_CortarAudios.md). Donde discrepen, manda esa. En particular, la reproducción se hace con el archivo local del navegador, sin vista previa en el servidor.

## Qué hay en el binario

Análisis estático con REA (`open_binary`, `inspect_pe_resources`, `inspect_with_objdump`), `objdump -p` y `strings`. No se ejecutó nada.

- `audacity-win-2.0.4.exe` (SHA-256 `7bc93c9a…5b01b4`) es un **instalador Inno Setup**: un stub Delphi de 0,4 MB (recursos `DVCLAL`, `PACKAGEINFO`) con 21,9 MB de carga comprimida. Se desempaquetó con `innoextract 1.9` para llegar al programa.
- `app/audacity.exe` (7,4 MB, SHA-256 `74a251fb…655d41`): PE32 i386 de agosto de 2013, compilado con MSVC 2008 (`msvcr90.dll`) sobre wxWidgets 2.8. Conserva nombres C++ y RTTI, así que sus clases se leen directamente.
- REA no pudo desensamblar: no hay Ghidra, IDA, Hopper ni Kuna configurados (`GHIDRA_INSTALL_DIR` sin definir). Tampoco hace falta, porque Audacity es software libre (GPLv2) y su código está publicado. Ese mismo motivo impide copiar código suyo en 4toTOP: se toma el diseño, no el código.
- `Lame_v3.99.3_for_Windows.exe` solo instala `lame_enc.dll`, que Audacity 2.0.4 carga para exportar MP3. FFmpeg con `libmp3lame` cubre ese papel aquí.

### Cómo trabaja Audacity

| Pieza | Evidencia en el binario | Qué significa para la web |
|---|---|---|
| Edición no destructiva | `WaveTrack`, `WaveClip`, `Sequence`, `BlockFile`, `SimpleBlockFile`, `PCMAliasBlockFile`, `ODPCMAliasBlockFile`, `DirManager` | El audio original no se toca: se divide en bloques con resúmenes (mín./máx./RMS) para dibujar la onda, y los cortes son una lista de tramos. Lo mismo aquí: el navegador edita una lista de tramos y el servidor la aplica al exportar |
| Carga diferida | `ODPCMAliasBlockFile` (*on demand*) | La onda se dibuja desde resúmenes, sin decodificar el archivo entero. En la web: picos precalculados en el servidor, sin decodificar horas de audio en el navegador |
| Deshacer | `UndoManager` | Pila de deshacer en el cliente |
| Menú Editar | `Cut`, `Delete`, `Trim`, `SplitCut`, `SplitDelete`, `SplitNew`, `Silence`, `Duplicate`, `Paste`, `ZeroCross`, `Detach at Silences` | Ver operaciones abajo |
| Efectos de uso diario | `EffectFadeIn`, `EffectFadeOut`, `EffectNormalize`, `EffectAmplify`, `EffectNoiseRemoval`, `EffectChangeTempo`, `EffectChangeSpeed`; plug-ins `SilenceMarker.ny`, `SoundFinder.ny`, `StudioFadeOut.ny` | Fundidos, normalizar y detectar silencios se resuelven con filtros de FFmpeg |
| Importar | libsndfile 1.0.24 (WAV, AIFF, FLAC…), `MP3ImportPlugin` (libmad), Ogg, `FFmpegImportPlugin` opcional (`avformat-52.dll`: M4A, WMA, AMR, AC3, audio de video) | FFmpeg lee todos esos formatos |
| Exportar | `ExportPCM`, `ExportMP3`, `ExportOGG`, `ExportFLAC`, `ExportMP2` (twolame), `ExportFFmpeg` (AAC, AC3, AMR-NB, WMA), `ExportMultiple` | MP3, WAV, M4A, OGG y FLAC; varias regiones en un ZIP |
| Otras bibliotecas enlazadas | SoundTouch, SBSMS, soxr, PortAudio, Vamp, LV2, LADSPA | Tempo/tono, remuestreo, reproducción y plug-ins: fuera del alcance de un cortador |

## Operaciones a ofrecer

| Audacity | Web | FFmpeg |
|---|---|---|
| Delete / Cut | Quitar la selección | `atrim` de los tramos que quedan + `concat` |
| Trim | Conservar solo la selección | `atrim=start:end` |
| Split New / Export Multiple | Exportar cada región como archivo (ZIP) | Un `atrim` por región |
| Silence Audio | Silenciar la selección | `volume=0:enable='between(t,a,b)'` |
| Fade In / Fade Out | Fundido al inicio, al final y en cada unión | `afade`, `acrossfade` |
| Normalize | Normalizar volumen | `loudnorm` |
| Detach at Silences / SoundFinder | Proponer cortes en los silencios | `silencedetect` |

Los cortes se recodifican siempre. `-c copy` es rápido, pero en MP3 y AAC solo corta en límites de trama (unos 26 ms) y deja clics, y no sirve para fundidos.

## Diseño en 4toTOP

Se agrega como una herramienta más de `config/conversions.php`, igual que `AudioToMp3Tool`, para reutilizar la cola, el registro, el token de descarga y la expiración.

1. **`AudioCutTool`** (`'audiocut'`, grupo `Editar`, ícono `fa-scissors`). `accept`: `mp3`, `m4a`, `wav`, `ogg`, `opus`, `flac`, `wma`, `aac`, `amr`. Opciones: `segments` (lista `[inicio, fin]` en segundos con 3 decimales), `mode` (`keep` o `remove`), `separate` (un archivo por región), `fade_ms`, `normalize`, `format`.
2. **`prepare()`** valida contra la duración real: tramos dentro del audio, sin solaparse, al menos 0,1 s de resultado y como máximo 200 tramos. Errores bajo `options.segments`.
3. **Sondeo al subir**, como `needsPageCount()` en los PDF: un servicio `AudioInspector` con `ffprobe` (duración, canales, frecuencia) y `ffmpeg -ac 1 -ar 8000 -f s16le -` para calcular unos 50 picos por segundo (1 h ≈ 180.000 valores, unos 700 KB en JSON). Es el equivalente de los resúmenes de `BlockFile`.
4. **`process()`** arma un único `-filter_complex` con `atrim`/`asetpts`/`concat` (o `acrossfade` si hay fundido) y codifica según `format`. `timeout()` proporcional a la duración. Agregar `ffprobe` a la configuración y que `enabled()` oculte la herramienta si FFmpeg no está instalado. **Hoy no está instalado en esta máquina.**
5. **Editor en el navegador**: componente Alpine en `file-converter.blade.php` con `wavesurfer.js` 7 (licencia BSD-3) y su plugin Regions. Usa el elemento `<audio>` como reproductor y los picos del paso 3, así que no decodifica el archivo entero (1 h estéreo a 44,1 kHz ocuparía unos 1,3 GB de RAM). Incluye zoom, regiones arrastrables, reproducir la selección, campos numéricos de inicio y fin sincronizados con la onda, deshacer, y los atajos de Audacity: espacio, `Ctrl+K` borrar, `Ctrl+T` recortar, `Ctrl+I` dividir, `Ctrl+L` silenciar, `Ctrl+Z`. Se importa de forma dinámica desde `resources/js/app.js` para no cargarlo en las demás páginas.
6. **Reproducir lo subido**: `temporaryUrl()` de Livewire ya admite `wav`, `mp3`, `m4a` y `wma` (`preview_mimes` en `config/livewire.php`). Hay que agregar `ogg`, `opus`, `flac`, `aac` y `amr`. El navegador no reproduce WMA ni AMR: para esos, el servidor genera una vista previa en MP3 durante el sondeo.

## Fases

1. `AudioCutTool` con campos numéricos de inicio y fin, sin onda. Ya sirve para cortar. Tests Pest con audio sintético (`ffmpeg -f lavfi -i sine=…`): duración del resultado ±10 ms, tramos inválidos rechazados y región múltiple entregada como ZIP.
2. `AudioInspector` y el editor con onda, regiones, reproducción y atajos.
3. Fundidos, normalizar y proponer cortes por silencio.

## Decisiones pendientes

- **Tamaño máximo.** El límite actual es de 50 MB por subida de Livewire: unos 55 min en MP3 a 128 kbps, pero solo 5 min en WAV de 44,1 kHz. Si hay que cortar audios de audiencia más largos, hay que subir `max_kb` y el límite de Livewire, o subir por partes.
- **Acceso.** Pública en `/convertir-archivos` (límite `public_limit`) o solo en el backoffice.
- **Guardar el trabajo.** En esta propuesta cada corte es una conversión de una sola vez, como las demás herramientas. Guardar proyectos editables, como el `.aup` de Audacity, sería una función aparte.
