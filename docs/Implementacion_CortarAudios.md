# Implementación: Cortar audio en /convertir-archivos

Fecha: 26 de octubre de 2026. Contexto y análisis de Audacity: [plan-cortar-audio.md](plan-cortar-audio.md). Si los dos documentos discrepan, manda este.

Este documento es una orden de trabajo para un agente. Sigue los pasos en orden; cada uno termina con una verificación. No cambies el alcance ni las decisiones de la sección 2 sin consultar al usuario.

---

## 0) Antes de empezar

1. **El sistema de herramientas no está en `main`.** `app/Conversions/`, `PdfPageTools`, `resources/views/livewire/partials/conversion-tools/`, `resources/js/pdf-thumbnails.js`, `tests/Helpers/conversions.php`, `tests/Feature/PdfToolsTest.php`, `CheckConversionTools` y la migración `2026_10_26_000000_add_options_to_file_conversions_table.php` solo existen como cambios sin commitear en la copia de trabajo del usuario (`/home/yorsh/Jobs/4toTOP`). Este plan se apoya en ellos. Si trabajas en un worktree o rama nueva y no encuentras `app/Conversions/ConversionTool.php`, **detente y pide al usuario que haga commit de ese trabajo**. No lo copies por tu cuenta.
2. **Instala FFmpeg** (con `ffprobe`) si no existe: `ffmpeg -version && ffprobe -version`. En Ubuntu: `sudo apt install ffmpeg`. Necesitas los codificadores `libmp3lame`, `aac`, `libvorbis` y `flac` (`ffmpeg -hide_banner -encoders | grep -E 'libmp3lame|aac|libvorbis|flac'`). Sin FFmpeg, los tests de audio se omiten y no podrás verificar nada.
3. Lee estos archivos antes de escribir código. Son el contrato que debes respetar:
   - `app/Conversions/ConversionTool.php`: `defaults()`, `rules()`, `prepare()`, `timeout()`, `process()`, `resultExtension()` y `resultSuffix()`.
   - `app/Conversions/AudioToMp3Tool.php` (FFmpeg) y `app/Conversions/SplitPdfTool.php` (ZIP con `deliver()`; una sola parte se entrega sin ZIP).
   - `app/Conversions/OrganizePdfTool.php` y `resources/views/livewire/partials/conversion-tools/organize.blade.php`: editor en el navegador con `wire:ignore` que envía su plan con `$wire.set('options.plan', …, false)`.
   - `app/Livewire/Concerns/ConvertsFiles.php`: `updatedFile()`, `convert()` y `selectFormat()`.
   - `app/Jobs/ConvertFile.php`: renombra el resultado según la extensión real del archivo entregado.
   - `resources/js/app.js`: `window.pdfOrganizer` importa su módulo de forma dinámica.
   - `config/conversions.php` y `tests/Helpers/conversions.php`.
4. Convenciones del repo (ver `AGENTS.md`): textos de la interfaz en español; comentarios escasos, en inglés y solo donde expliquen un porqué; Pint (`./vendor/bin/pint --test`); los tests usan MySQL `sistoficiolibertades_test` con `RefreshDatabase`. No renombres archivos heredados.

---

## 1) Objetivo

Agregar la herramienta **«Cortar audio»** al conversor (`/convertir-archivos` y `/backoffice/convertir-archivos`). El usuario sube un audio y ve su forma de onda. Marca una o más regiones con el mouse o escribiendo los tiempos, las escucha y elige qué hacer con ellas:

| Modo | Equivalente en Audacity | Resultado |
|---|---|---|
| `keep` | Recortar (*Trim*) | Se conservan solo las regiones marcadas, unidas en orden |
| `keep` + `separate` | Dividir nuevo / Exportar múltiple | Cada región en su propio archivo (ZIP; si hay una sola, el archivo solo) |
| `remove` | Borrar (*Delete*) | Se quitan las regiones marcadas y se une lo demás |
| `silence` | Silenciar audio | Misma duración; las regiones quedan en silencio |

Además: fundido en las uniones (`fade_ms`), normalización de volumen (`normalize`) y formato de salida (`mp3`, `wav`, `m4a`, `ogg`, `flac`).

El servidor nunca confía en los tiempos del navegador: vuelve a medir la duración con `ffprobe` y valida cada región.

---

## 2) Decisiones tomadas (no cambiar sin el usuario)

- **Tamaño máximo: 50 MB**, el límite actual de Livewire (`config/livewire.php`) y de M4A a MP3. Duración máxima: 4 horas (`CONVERSIONS_AUDIO_MAX_SECONDS=14400`).
- **Acceso:** igual que las demás herramientas. Pública, con `public_limit` usos por sesión, y en el backoffice.
- **Sin proyectos guardados:** cada corte es una conversión de una sola vez, como las demás herramientas.
- **Siempre se recodifica.** No se usa `-c copy`, que en MP3 y AAC corta en límites de trama (unos 26 ms), deja clics e impide los fundidos.
- **Reproducción en el navegador con el archivo local** (`URL.createObjectURL`), sin pedir nada al servidor. Si el navegador no puede reproducir el formato (WMA, AMR y, según el navegador, FLAC u Ogg), la onda y la edición funcionan igual; solo se desactiva la reproducción y se avisa.
- **Picos de la onda calculados en el servidor** al subir el archivo, no con `decodeAudioData`: una hora estéreo a 44,1 kHz ocupa unos 1,3 GB de RAM en el navegador.
- **Librería del editor: `wavesurfer.js@7.12.12`** (BSD-3-Clause, publicada el 10/09/2026) con su plugin Regions. La serie 8.x es demasiado reciente: no la uses.
- **No copiar código de Audacity** (GPLv2). Se toma su diseño, no su código.

---

## 3) Archivos

| Acción | Archivo |
|---|---|
| Crear | `app/Conversions/AudioCutTool.php` |
| Crear | `app/Services/AudioInspector.php` |
| Crear | `resources/views/livewire/partials/conversion-tools/audiocut.blade.php` |
| Crear | `resources/js/audio-editor.js` |
| Crear | `tests/Feature/AudioCutTest.php` |
| Modificar | `config/conversions.php`: herramienta `audiocut` y claves `ffprobe`, `audio_max_seconds` y `audio_cut_max_segments` |
| Modificar | `app/Conversions/ConversionTool.php`: `needsAudioProbe()` |
| Modificar | `app/Conversions/SplitPdfTool.php`: `deliver()` recibe la extensión |
| Modificar | `app/Livewire/Concerns/ConvertsFiles.php`: sondeo de audio al subir |
| Modificar | `resources/js/app.js`: `window.audioCutter` |
| Modificar | `app/Console/Commands/CheckConversionTools.php`: FFprobe y codificadores |
| Modificar | `tests/Helpers/conversions.php`: utilidades de audio |
| Modificar | `package.json` / `package-lock.json`: `wavesurfer.js` 7.12.12 exacta |
| Modificar | `.env.example` y `docs/file-conversions.md` |

No hace falta ninguna migración: las opciones van en `file_conversions.options` (JSON, cast `array`).

---

## 4) Paso a paso

### Paso 1: configuración

En `config/conversions.php`, junto a `'ffmpeg'`:

```php
'ffprobe' => env('CONVERSIONS_FFPROBE', 'ffprobe'),
'audio_max_seconds' => (int) env('CONVERSIONS_AUDIO_MAX_SECONDS', 4 * 3600),
'audio_cut_max_segments' => 200,
```

En `tools`, después de `'pagenum'` (grupo `Editar`):

```php
'audiocut' => ['label' => 'Cortar audio', 'group' => 'Editar', 'icon' => 'fa-scissors', 'class' => AudioCutTool::class, 'accept' => ['mp3', 'm4a', 'wav', 'ogg', 'oga', 'opus', 'flac', 'aac', 'wma', 'amr'], 'max_kb' => 50 * 1024],
```

**Riesgo a verificar en el paso 8:** `File::types()` valida con la extensión que deduce del contenido (MIME), no solo con la del nombre. Un `.opus` suele detectarse como `audio/ogg` y un `.aac` crudo como `audio/x-hfe` o `application/octet-stream`. Para cada formato de la lista, genera un archivo real con FFmpeg y comprueba que pase la validación. Si un formato no pasa y no se arregla agregando su alias (por ejemplo `oga`), **quítalo de `accept`** y anótalo en `docs/file-conversions.md`. No desactives la validación de tipo.

Agrega `CONVERSIONS_FFPROBE=ffprobe` y `CONVERSIONS_AUDIO_MAX_SECONDS=14400` en `.env.example`, junto a `CONVERSIONS_FFMPEG` si está; si no, en el bloque de conversiones.

**Verificación:** `php artisan tinker --execute="dump(array_keys(App\Conversions\ConversionTool::available()));"` incluye `audiocut`.

### Paso 2: `AudioInspector`

`app/Services/AudioInspector.php` sigue el estilo de `PdfInspector` y usa `Symfony\Component\Process\Process`, nunca un shell. Sus errores para el usuario son `\InvalidArgumentException` con el mensaje en español, que `ConvertsFiles` muestra en el campo `file`.

```php
/** @return array{duration: float, channels: int, sample_rate: int, codec: string} */
public function probe(string $path): array
```

- Ejecuta `[config('conversions.ffprobe'), '-v', 'error', '-select_streams', 'a:0', '-show_entries', 'stream=codec_name,channels,sample_rate:format=duration', '-of', 'json', $path]` con un timeout de 30 s.
- Si el proceso falla o no hay flujo de audio: `El archivo no contiene audio legible.`
- Si `duration` falta o es ≤ 0: el mismo mensaje.
- Si `duration > config('conversions.audio_max_seconds')`: `El audio dura X; el máximo es Y.` Formatea las duraciones con `self::clock()`.

```php
/** @return array{per_second: int, peaks: string} peaks: base64 of one byte (0–255) per bucket, the bucket's absolute maximum */
public function peaks(string $path, float $duration): array
```

- `per_second = max(1, min(50, intdiv(200_000, (int) ceil($duration))))`: como máximo 200.000 puntos (unos 270 KB en base64).
- Decodifica a mono, 8 kHz, PCM de 16 bits: `[ffmpeg, '-nostdin', '-v', 'error', '-i', $path, '-vn', '-ac', '1', '-ar', '8000', '-f', 's16le', '-']`. Usa `Process::start()` y lee la salida por partes con `getIncrementalOutput()` o un iterador, para no tener toda la señal en memoria (una hora son 57 MB). Timeout de 120 s.
- Cada cubo tiene `intdiv(8000, per_second)` muestras. Calcula `max(abs(muestra))`, escálalo a 0–255 con `(int) round($max / 32768 * 255)` y escribe un byte. Usa `unpack('v*')` sobre bloques de longitud par y convierte a entero con signo (`$s >= 32768 ? $s - 65536 : $s`). Guarda los bytes sobrantes de un bloque para el siguiente.
- Si FFmpeg falla: `\RuntimeException('FFmpeg: '…)`, igual que `AudioToMp3Tool`.

```php
/** 3725.5 → "1:02:05.500"; 65.25 → "1:05.250" */
public static function clock(float $seconds): string
```

**Verificación:** con un tono de 10 s (ver el paso 8, `conversionAudio()`), `probe()` devuelve una duración de 10 ± 0,05 s y `peaks()` devuelve 500 ± 2 bytes con valores mayores que 0.

### Paso 3: `AudioCutTool`

`app/Conversions/AudioCutTool.php extends ConversionTool`.

```php
public function needsAudioProbe(): bool { return true; }
public function resultExtension(): string { return 'mp3'; } // ConvertFile renames to the delivered extension
public function resultSuffix(): string { return '_recortado'; }

public function defaults(): array
{
    return ['segments' => '[]', 'mode' => 'keep', 'separate' => false, 'fade_ms' => 0, 'normalize' => false, 'output' => 'mp3'];
}

public function rules(): array
{
    return [
        'segments' => ['required', 'string', 'max:20000'],
        'mode' => ['required', 'in:keep,remove,silence'],
        'separate' => ['boolean'],
        'fade_ms' => ['required', 'integer', 'in:0,100,500,2000'],
        'normalize' => ['boolean'],
        'output' => ['required', 'in:'.implode(',', array_keys(self::ENCODERS))],
    ];
}
```

Codificadores (constante pública, la usan la vista y el comando de verificación):

```php
public const ENCODERS = [
    'mp3' => ['label' => 'MP3', 'args' => ['-codec:a', 'libmp3lame', '-b:a', '192k']],
    'm4a' => ['label' => 'M4A (AAC)', 'args' => ['-codec:a', 'aac', '-b:a', '192k', '-movflags', '+faststart']],
    'wav' => ['label' => 'WAV', 'args' => ['-codec:a', 'pcm_s16le']],
    'ogg' => ['label' => 'OGG', 'args' => ['-codec:a', 'libvorbis', '-q:a', '5']],
    'flac' => ['label' => 'FLAC', 'args' => ['-codec:a', 'flac']],
];
```

No fuerces la frecuencia ni los canales: se conservan los del original.

#### Lógica pura (testeable sin FFmpeg)

```php
/**
 * The spans the result is made of: the marked regions for keep, their complement for remove, and the
 * regions to mute for silence. Regions are rounded to milliseconds, clamped to the audio, sorted and merged
 * when they overlap or touch.
 *
 * @param  list<array{0: float|int, 1: float|int}>  $regions
 * @return list<array{0: float, 1: float}>
 * @throws \InvalidArgumentException with the message for options.segments
 */
public static function spans(array $regions, string $mode, float $duration): array
```

Reglas, en este orden:

1. Cada región debe ser una lista de dos números finitos. Si no: `Las regiones no son válidas. Vuelve a marcarlas.`
2. Redondea a 3 decimales. Una región que empieza en `< -0.05` o termina en `> duration + 0.05` es inválida: `La región N (inicio–fin) está fuera del audio, que dura D.` (N empieza en 1, en el orden recibido). Dentro de esa tolerancia, se ajusta a `[0, duration]`.
3. Si `inicio >= fin` o la región dura menos de 0,05 s: `La región N es demasiado corta.`
4. Sin regiones: `Marca al menos una región en la onda.`
5. Más de `audio_cut_max_segments` regiones: `Puedes marcar hasta 200 regiones.`
6. Ordena por inicio y une las que se solapan o se tocan (`siguiente.inicio <= actual.fin`).
7. Según el modo:
   - `keep`: las regiones unidas.
   - `remove`: el complemento en `[0, duration]`; descarta los tramos de menos de 0,01 s.
   - `silence`: las regiones unidas (son las que se silencian).
8. En `keep` y `remove`, si el total resultante es menor que 0,1 s: `El resultado quedaría vacío.`

#### `prepare()`

```php
public function prepare(array $options, array $files, ?int $pageCount): array
```

1. `$duration = app(AudioInspector::class)->probe($files[0]->getRealPath())['duration']`. Si lanza `InvalidArgumentException`, conviértela en `ValidationException` con la clave `file`.
2. `json_decode($options['segments'], true, 4)`; si no es una lista: error en `options.segments`.
3. `spans()`; si lanza `InvalidArgumentException`: error en `options.segments`.
4. `separate` solo vale con `mode === 'keep'`; en los otros modos se guarda `false`.
5. **Fundido:** en `keep` (sin `separate`) y `remove`, si hay más de un tramo y `fade_ms > 0`, cada tramo debe durar al menos `2 × fade_ms`. Si no: `options.fade_ms` → `El fundido es más largo que el tramo N; elige uno más corto.`
6. Guarda y devuelve: `['mode', 'spans', 'separate', 'fade_ms', 'normalize', 'output', 'duration']`. `spans` y `duration` vienen del servidor, no del navegador.

#### `timeout()`

`return max(360, (int) ceil(($options['duration'] ?? 0) / 10) + 120);`. Mide en el paso 8 cuánto tarda codificar 4 horas. Si el timeout supera 360 s, el `retry_after` de la cola debe ser mayor: replica para el audio la comprobación `queue()` de `CheckConversionTools` (hoy usa `OcrPdfTool::ocrTimeout()`). Toma el mayor de los dos valores.

#### `process()`

```php
public function process(FileConversion $conversion, string $directory): ToolResult
```

- `$this->engine = 'ffmpeg'`, y `$input = $directory.'/'.basename($conversion->input_path)`.
- Toma `$o = $conversion->options` y `$encoder = self::ENCODERS[$o['output']]['args']`.
- Base de todos los comandos: `[ffmpeg, '-nostdin', '-y', '-v', 'error']`. Siempre como arreglo de `Process`; los números van formateados con `sprintf('%.3F', …)`, que usa punto decimal sin importar la configuración regional.
- **`keep` sin `separate`, y `remove`:** un solo comando con `-filter_complex`:
  - Por cada tramo `i`: `[0:a]atrim=start=S:end=E,asetpts=PTS-STARTPTS[a{i}]`.
  - Unión:
    - Con un solo tramo, la salida es `[a0]`.
    - Con `fade_ms = 0`: `[a0][a1]…concat=n=N:v=0:a=1[cat]`.
    - Con fundido, encadena `acrossfade=d=F:c1=tri:c2=tri` por pares: `[a0][a1]acrossfade…[x1];[x1][a2]acrossfade…[x2]`… Cada unión acorta el resultado en F.
  - Con `normalize`, agrega `,loudnorm=I=-16:TP=-1.5:LRA=11` al final de la cadena antes de la etiqueta de salida `[out]`.
  - `-map '[out]'`, más `$encoder` y `$directory.'/result.'.$o['output']`.
- **`silence`:** `-af "volume=enable='between(t,S1,E1)+between(t,S2,E2)…':volume=0"` (más `,loudnorm…` si corresponde), `-vn`, `$encoder`, salida. Con 200 regiones la expresión mide unos 6 KB: se pasa como un argumento, sin límite práctico.
- **`keep` con `separate`:** un comando por tramo: `[…base, '-ss', S, '-to', E, '-i', $input, '-vn', …(fade_ms > 0 ? ['-af', 'afade=t=in:d=F,afade=t=out:st=(E-S-F):d=F'] : []), …$encoder, $work.'/'.sprintf('%s_%02d.%s', $base, $i + 1, $ext)]`. `$work = $directory.'/cut'` y `$base` es el nombre original sin extensión, limitado a 120 caracteres, como en `SplitPdfTool`. Con `-ss` antes de `-i` el corte es exacto porque se recodifica. Entrega con `SplitPdfTool::deliver($parts, $directory, $o['output'])` (ver el paso 4) y borra `$work` en un `finally`.
- Timeout de cada `Process`: `$this->timeout($o)`. Si falla: `\RuntimeException('FFmpeg: '.mb_substr($process->getErrorOutput(), 0, 500))`.
- **Comprueba el resultado:** mide con `probe()` cada archivo producido y compáralo con la duración esperada:
  - `keep`/`remove`: la suma de los tramos menos `(N − 1) × F` si hay fundido.
  - `silence`: la duración original.
  - `separate`: `E − S` de cada tramo.
  
  Si alguno difiere en más de 0,1 s, más una trama del códec (usa 0,15 s en total), lanza `\RuntimeException('La duración del audio cortado no coincide con lo marcado.')`.
- **Aviso** (`ToolResult::notice`), por ejemplo:
  - `keep`: `Se conservaron 3 regiones. Duración final: 12:05.000.`
  - `remove`: `Se quitaron 2 regiones (1:20.000). Duración final: 10:40.000.`
  - `silence`: `Se silenciaron 2 regiones; la duración no cambia.`
  - `separate`: `Se crearon 4 archivos.`

### Paso 4: `SplitPdfTool::deliver()` con extensión

Cambia la firma a `deliver(array $parts, string $directory, string $extension = 'pdf'): string`. Una parte se mueve a `result.{$extension}`. El mensaje de error del ZIP pasa a `No se pudo crear el ZIP.` y el de una parte a `No se pudo guardar el archivo.`. Revisa con `grep -rn "deliver(" app tests` que los llamadores actuales sigan funcionando; el valor por defecto los cubre. Los tests de PDF deben seguir pasando.

### Paso 5: `ConversionTool` y `ConvertsFiles`

En `ConversionTool`, junto a `needsPageCount()`:

```php
/** Whether the page measures the uploaded audio (duration and waveform peaks) as soon as it arrives. */
public function needsAudioProbe(): bool
{
    return false;
}
```

En `ConvertsFiles`:

1. Nueva propiedad `public ?float $audioDuration = null;`.
2. En `updatedFile()`, antes del `return` de `needsPageCount()`, aplica este flujo:

   ```php
   $this->audioDuration = null;
   if ($this->tool()->needsAudioProbe()) {
       $this->validate(['file' => ['required', ...$this->fileRules()]]);
       try {
           $inspector = app(AudioInspector::class);
           $this->audioDuration = $inspector->probe($this->file->getRealPath())['duration'];
           $peaks = $inspector->peaks($this->file->getRealPath(), $this->audioDuration);
       } catch (\InvalidArgumentException $exception) {
           throw ValidationException::withMessages(['file' => $exception->getMessage()]);
       }
       $this->dispatch('audio-loaded', duration: $this->audioDuration, perSecond: $peaks['per_second'], peaks: $peaks['peaks']);

       return;
   }
   ```

   **Los picos no se guardan en una propiedad pública.** Livewire reenvía las propiedades públicas en cada petición, y los picos pesan hasta 270 KB. Viajan una sola vez, en el evento.
3. Agrega `'audioDuration'` a los dos `reset(...)` de `selectFormat()` y `convert()`.
4. `convert()` ya llama a `$tool->prepare()`, que vuelve a medir el audio: no hace falta nada más.

**Verificación:** `Livewire::test(PublicFileConverterPage::class)->call('selectFormat', 'audiocut')->set('file', <tono de 10 s>)` → `assertSet('audioDuration', …)` (± 0,05) y `assertDispatched('audio-loaded')`.

### Paso 6: editor en el navegador

#### Dependencia

`npm install --save-exact wavesurfer.js@7.12.12`. Comprueba en `package.json` que quedó `"wavesurfer.js": "7.12.12"`, sin `^`.

#### `resources/js/audio-editor.js`

Es el módulo que importa `wavesurfer.js` y `wavesurfer.js/dist/plugins/regions.esm.js`. Se carga solo con `import('./audio-editor')`, para que el resto del sitio no lo descargue (igual que `pdf-thumbnails.js`). Exporta:

```js
/**
 * Draws the waveform of a local audio file from server-computed peaks and lets the user mark regions.
 * The audio plays from the local file (object URL); nothing is downloaded from the server.
 * Returns { wavesurfer, regions, destroy() }.
 */
export function createEditor({ container, file, duration, perSecond, peaks, onChange, onTime, onPlayable })
```

- Decodifica los picos con `Uint8Array.from(atob(peaks), c => c.charCodeAt(0))` y luego `Float32Array.from(bytes, b => b / 255)`.
- `const media = new Audio(); media.preload = 'metadata'; media.src = URL.createObjectURL(file);`
  - `media.addEventListener('error', () => onPlayable(false))` y `canplay → onPlayable(true)`.
  - Si `media.canPlayType(file.type) === ''`, llama a `onPlayable(false)` de inmediato.
- `WaveSurfer.create({ container, media, peaks: [floats], duration, height: 128, waveColor: '#94a3b8', progressColor: '#0A2E5D', cursorColor: '#0369a1', barWidth: 2, barGap: 1, normalize: true, minPxPerSec: 1, dragToSeek: true })`.
  - Con `peaks` y `duration`, wavesurfer 7 no decodifica el archivo: verifícalo en la pestaña Red/Memoria con un archivo de 50 MB.
  - Si `media` no puede reproducir, sigue usándolo igual: wavesurfer dibuja desde los picos.
- `const regions = wavesurfer.registerPlugin(RegionsPlugin.create())` y `regions.enableDragSelection({ color: 'rgba(3, 105, 161, 0.25)' })`.
- Avisa a `onChange(regions.getRegions())` en `region-created`, `region-updated` y `region-removed`, y a `onTime(t)` en `timeupdate`/`interaction`.
- `destroy()`: `wavesurfer.destroy()` y `URL.revokeObjectURL(media.src)`.

#### `window.audioCutter` en `resources/js/app.js`

Va junto a `window.pdfOrganizer`, con el mismo estilo. Estado de Alpine:

```js
window.audioCutter = (maxRegions) => ({
    file: null, peaksEvent: null, editor: null,
    loading: false, error: null, playable: true, playing: false,
    regions: [],        // [{ id, start, end }] in seconds, ordered by start; the source of truth for the table and sync()
    selected: null,     // id of the selected region
    time: 0, zoom: 1,
    history: [], future: [],   // undo / redo: snapshots of regions (max 50)
    // load(file), loaded(event), build(), reset(), sync(), snapshot(), undo(), redo(), play(), playRegion(id),
    // addAtCursor(), splitAtCursor(), removeRegion(id), setBoundary('start'|'end'), setTime(id, field, text),
    // zoomBy(factor), keydown(event), clock(seconds), parse(text)
});
```

Comportamiento:

- `load(file)`: guarda el `File` y destruye el editor anterior; con `file = null` limpia todo.
- `loaded({ detail })`: guarda `{ duration, perSecond, peaks }` del evento Livewire `audio-loaded`.
- `build()` crea el editor cuando ya tiene **el archivo y los picos**, en cualquier orden. Importa con `await import('./audio-editor')`, maneja los errores como `pdfOrganizer` y muestra `No se pudo leer el audio para mostrar su onda.`
- `sync()` sigue el patrón de Organizar:

  ```js
  this.$wire.set('options.segments', JSON.stringify(this.regions.map(r => [+r.start.toFixed(3), +r.end.toFixed(3)])), false)
  ```

  Se llama en cada cambio, de forma diferida: viaja con el envío del formulario.
- Antes de cada cambio del usuario (crear, mover, borrar, dividir o editar un tiempo), `snapshot()` guarda `regions` en `history` y vacía `future`. Deshacer y rehacer reconstruyen las regiones del plugin desde la instantánea con `clearRegions()` + `addRegion()`.
- Si se intenta crear una región más allá de `maxRegions`, quítala y muestra `Puedes marcar hasta N regiones.`
- `setTime(id, field, text)` lee `h:mm:ss.mmm`, `m:ss.mmm` o segundos. Si el texto no es válido o deja `inicio >= fin`, marca el campo con `aria-invalid` y no cambia nada.
- `splitAtCursor()`: si el cursor está dentro de una región, la divide en dos en ese punto (como *Split* de Audacity).
- `addAtCursor()`: crea una región de 5 s, o hasta el final, desde el cursor.
- Zoom: `editor.wavesurfer.zoom(px)` con `px = 1 × 2^n`, de 1 a 1.000 px/s.

Atajos, tomados de Audacity. Solo funcionan con el foco dentro del contenedor del editor (`tabindex="0"`, `x-on:keydown="keydown($event)"`) y se ignoran si el foco está en un `input`, `select` o `textarea`:

| Tecla | Acción |
|---|---|
| Espacio | Reproducir / pausar |
| `Shift+Espacio` | Reproducir la región seleccionada |
| `Inicio` / `Fin` | Ir al inicio / al final |
| `←` / `→` | Mover el cursor 1 s (con `Shift`: 10 s) |
| `[` / `]` | Fijar el inicio / fin de la región seleccionada en el cursor |
| `Ctrl+I` | Dividir la región en el cursor |
| `Supr` / `Retroceso` | Quitar la región seleccionada |
| `Ctrl+Z` / `Ctrl+Shift+Z` o `Ctrl+Y` | Deshacer / rehacer |
| `Ctrl+1` / `Ctrl+3` / `Ctrl+2` | Acercar / alejar / zoom normal |

Llama a `preventDefault()` solo en los atajos que se manejan.

#### `resources/views/livewire/partials/conversion-tools/audiocut.blade.php`

Sigue la estructura de `organize.blade.php`: `wire:key="audiocut-{{ $format }}"` y un bloque `wire:ignore` con

```blade
x-data="audioCutter({{ config('conversions.audio_cut_max_segments') }})"
x-on:change.document="if ($event.target.id === 'conversion-file') load($event.target.files[0])"
x-on:audio-loaded.window="loaded($event)"
x-on:conversion-queued.window="load(null)"
```

Contenido, en este orden:

1. Mientras se analiza el archivo, `wire:loading wire:target="file"`: `Analizando el audio…` (`role="status"`).
2. Errores (`role="alert"`) y el aviso `Este formato no se puede escuchar en el navegador. Puedes cortarlo igual usando la onda y los tiempos.` cuando `!playable`.
3. Barra de controles (botones con `aria-label` e íconos `fa-solid`): reproducir/pausa, reproducir región, agregar región, dividir en el cursor, deshacer, rehacer, acercar, alejar. Al lado, el tiempo actual y la duración en `font-mono`.
4. El contenedor de la onda (`x-ref="wave"`, `tabindex="0"`, `aria-label="Forma de onda. Arrastra para marcar una región."`), con un anillo de foco visible.
5. Una línea de ayuda: `Arrastra sobre la onda para marcar regiones. Atajos: Espacio reproduce, Supr quita la región, Ctrl+Z deshace.`
6. Una tabla de regiones (`<ol>`): número, campos de inicio y fin (`inputmode="decimal"`, `x-on:change="setTime(...)"`), duración y botones para reproducir y quitar. Al hacer clic en una fila se selecciona la región en la onda.
7. Fuera de `wire:ignore`, en un `fieldset` como el de `split.blade.php`:
   - El modo, como tres botones con `aria-pressed`: `Conservar lo marcado`, `Quitar lo marcado` y `Silenciar lo marcado`. Al cambiarlo, Alpine debe recolorear las regiones (verde suave para `keep`, rojo suave para `remove`, gris para `silence`). Usa `x-effect` o `$wire.$watch('options.mode', …)`.
   - Si el modo es `keep`, la casilla `Guardar cada región en un archivo aparte`.
   - Un `select` de fundido: `Sin fundido` (0), `Suave (0,1 s)` (100), `Medio (0,5 s)` (500), `Largo (2 s)` (2000).
   - La casilla `Normalizar el volumen`.
   - Un `select` de formato de salida desde `AudioCutTool::ENCODERS`.
   - Debajo de cada control, sus `@error('options.…')`, más `@error('options.segments')`.
8. Si existe `$audioDuration`, la línea `El audio dura {{ AudioInspector::clock($audioDuration) }}.`, como la de `pageCount` en `file-converter.blade.php`.

Revisa la accesibilidad: contraste AA, objetivos táctiles de al menos 40 px, todo usable con teclado y, en pantallas angostas, la tabla en columna.

**Verificación:** `npm run build` sin errores. Además, el chunk de `audio-editor` debe ser aparte: `public/build/manifest.json` tiene una entrada para `resources/js/audio-editor.js` y `app-*.js` no crece más de unos 2 KB.

### Paso 7: `conversions:check`

- Agrega la fila `$this->check('FFprobe', 'Cortar audio', [config('conversions.ffprobe'), '-version'])`.
- Cambia el «Usado por» de FFmpeg a `M4A a MP3, Cortar audio`.
- Agrega una fila que ejecute `ffmpeg -hide_banner -encoders` y compruebe que existe cada códec de `AudioCutTool::ENCODERS` (`libmp3lame`, `aac`, `pcm_s16le`, `libvorbis`, `flac`). El detalle lista los que faltan.

### Paso 8: tests

En `tests/Helpers/conversions.php`:

```php
function ffmpegAvailable(): bool // ffmpeg -version and ffprobe -version succeed with the configured paths

/** Generates a sine tone with FFmpeg; returns its contents. Codecs by extension: mp3 libmp3lame, m4a/aac aac, ogg/oga libvorbis, opus libopus, flac, wav pcm_s16le, wma wmav2, amr libopencore_amrnb (8 kHz mono). */
function conversionAudio(string $extension, float $seconds = 10, int $frequency = 440): string

function audioDuration(string $path): float // via AudioInspector::probe()

/** Mean volume in dB of [start, end] (ffmpeg volumedetect), to check silenced spans. */
function meanVolume(string $path, float $start, float $end): float
```

`tests/Feature/AudioCutTest.php`. Todos los tests que usan FFmpeg terminan con `->skip(fn () => ! ffmpegAvailable(), 'FFmpeg/ffprobe are not installed')`. Usa el patrón de `PdfToolsTest`: `Queue::fake()` en `beforeEach`; crea la conversión con `Livewire::test(PublicFileConverterPage::class)->call('selectFormat', 'audiocut')->set(...)->call('convert')`; toma `FileConversion::latest('id')->first()` y ejecuta `(new ConvertFile($job->id))->handle()`. Para revisar el timeout usa `Queue::assertPushed(ConvertFile::class, fn ($job) => …)`.

Sin FFmpeg (lógica pura de `AudioCutTool::spans()`; usa datasets):

1. `keep` ordena y une las regiones solapadas y contiguas: `[[5,7],[1,3],[2.5,4]]` en 10 s → `[[1,4],[5,7]]`.
2. `remove` devuelve el complemento: `[[0,2],[8,10]]` en 10 s → `[[2,8]]`, y `[[3,4]]` → `[[0,3],[4,10]]`.
3. Ajusta a `[0, duration]` dentro de la tolerancia (`[-0.03, 10.04]` → `[0, 10]`) y rechaza lo que se sale (`[9, 10.2]`).
4. Rechaza: sin regiones, `inicio >= fin`, menos de 0,05 s, valores no numéricos, más de 200 regiones, y en `keep`/`remove` un resultado de menos de 0,1 s (por ejemplo, `remove` de `[0, 10]`).
5. `AudioInspector::clock()`: `0` → `0:00.000`, `65.25` → `1:05.250` y `3725.5` → `1:02:05.500`.

Con FFmpeg:

6. La herramienta aparece en el grupo «Editar», y `?herramienta=audiocut` muestra el editor.
7. Al subir un tono de 10 s se fija `audioDuration` y se emite `audio-loaded` con un `perSecond` de 50 y unos 500 bytes de picos.
8. Un archivo que no es audio, renombrado a `.mp3`, da error en `file` y no consume el cupo (`used(...) === 0`). Lo mismo con un audio más largo que `audio_max_seconds`; para no generar 4 horas, baja el límite con `config()->set('conversions.audio_max_seconds', 5)`.
9. `keep` con `[[1,3],[6,7.5]]` → MP3 de 3,5 ± 0,15 s, el aviso esperado y el nombre `tono_recortado.mp3`.
10. `remove` con `[[2,4]]` → 8 ± 0,15 s.
11. `silence` con `[[2,4]]` → 10 ± 0,15 s; `meanVolume(2.2, 3.8) < -60` y `meanVolume(5, 9) > -30`.
12. `keep` + `separate` con tres regiones → `result.zip` con `tono_01.mp3`, `tono_02.mp3` y `tono_03.mp3` y la duración correcta de cada uno. Con una sola región → `tono_recortado.mp3`, sin ZIP.
13. Fundido de 500 ms con `[[0,3],[5,8]]` → 5,5 ± 0,15 s. Con `[[0,0.6],[5,8]]` → error en `options.fade_ms`.
14. `normalize` produce un archivo válido de la duración esperada.
15. Dataset de **salidas** (`mp3`, `m4a`, `wav`, `ogg`, `flac`): cada una se decodifica con `probe()` y dura lo esperado. Omite `ogg` si falta `libvorbis`.
16. Dataset de **entradas** (cada extensión de `accept`, generadas con `conversionAudio()`): pasan la validación de `file`, se miden y se cortan. Este test responde al riesgo del paso 1. Omite los formatos cuyo codificador falte en el FFmpeg local.
17. Regiones manipuladas: `options.segments` con `[[0, 999]]` en un audio de 10 s → error en `options.segments`. El servidor no confía en el cliente.
18. Los tests existentes de `SplitPdfTool`, `FileConversionTest` y `PdfToolsTest` siguen pasando después del cambio de `deliver()`.

Mide y anota en `docs/file-conversions.md` cuánto tardan `peaks()` y un corte `keep` en un MP3 de 1 hora (genéralo una vez fuera de los tests, con `conversionAudio('mp3', 3600)`). Si `peaks()` tarda más de 15 s, baja la frecuencia de decodificación a 4 kHz y documenta el porqué.

### Paso 9: verificación manual en el navegador

Con `composer run dev` y un trabajador de cola activo, usa Playwright, el MCP `mcp__playwright__*` o la skill `playwright-cli`:

1. Abre `/convertir-archivos?herramienta=audiocut` y sube un MP3 de unos 5 minutos. La onda aparece en menos de 5 s.
2. Arrastra para crear dos regiones, mueve el borde de una y pulsa `Ctrl+Z`: vuelve atrás. Escribe un tiempo inválido en la tabla: se marca y no cambia nada.
3. Espacio reproduce y `Shift+Espacio` reproduce solo la región.
4. Recorre los tres modos: los colores cambian. Convierte con «Quitar lo marcado», descarga el archivo y comprueba su duración con `ffprobe`.
5. Sube un `.wma`: aparece el aviso de que no se puede escuchar y aun así se puede cortar.
6. Recarga con 50 MB de MP3 y comprueba en DevTools > Memoria que la pestaña no supera unos 300 MB.
7. Repite el paso 2 solo con el teclado, sin mouse.
8. Captura la pantalla a 390 px de ancho: sin desplazamiento horizontal de la página. La onda sí puede desplazarse dentro de su contenedor.

### Paso 10: documentación

En `docs/file-conversions.md`:

- Una sección «Cortar audio» con los modos, los formatos aceptados y entregados, los límites (50 MB, 4 h y 200 regiones), la reproducción local, las mediciones del paso 8 y los formatos que se quitaron de `accept`, si alguno.
- En «Requisitos del servidor», FFmpeg con `ffprobe` y los codificadores necesarios.

---

## 5) Criterios de aceptación

- [ ] «Cortar audio» aparece en el grupo «Editar» del conversor público y del backoffice, y respeta `public_limit`.
- [ ] La onda de un audio de 50 MB se dibuja sin que el navegador decodifique el archivo.
- [ ] Los cuatro modos (`keep`, `keep` + `separate`, `remove` y `silence`) producen la duración esperada con una tolerancia de 0,15 s, verificada por el propio `process()`.
- [ ] El servidor rechaza las regiones fuera del audio, vacías, excesivas o manipuladas, sin consumir el cupo.
- [ ] Los cinco formatos de salida funcionan y se decodifican.
- [ ] Cada formato de `accept` tiene un test que lo valida, mide y corta.
- [ ] El editor se usa por completo con el teclado, y los atajos no interfieren con los campos de texto.
- [ ] `wavesurfer.js` tiene la versión exacta 7.12.12 y solo se descarga en esta herramienta.
- [ ] `php artisan conversions:check` informa sobre FFprobe y los codificadores.
- [ ] `./vendor/bin/pint --test`, `./vendor/bin/pest tests/Feature/AudioCutTest.php`, `./vendor/bin/pest tests/Feature/PdfToolsTest.php tests/Feature/FileConversionTest.php` y `npm run build` pasan.

## 6) Fuera de alcance

Grabar desde el micrófono, mezclar varias pistas, pegar o duplicar audio entre archivos, reducir ruido, cambiar tempo o tono, detectar silencios automáticamente (queda para una fase posterior con `silencedetect`), proyectos guardados como el `.aup` de Audacity, y subidas de más de 50 MB o por partes.

## 7) Entrega

Haz commits por paso (`feat(conversiones): …`, en español, como el historial) en una rama que no sea `main`. Al terminar, informa al usuario:

- Qué formatos de `accept` quedaron y cuáles se quitaron, y por qué.
- Las mediciones de tiempo.
- La salida de `conversions:check`.
- Cualquier test omitido y el motivo.
