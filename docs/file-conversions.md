# Conversión de archivos

> Referencia de calidad: [PDF a Word con Acrobat XI como referencia obligatoria](plan-pdf-word-acrobat-xi.md). El motor actual **no es Adobe**: busca una salida equivalente (párrafos, tablas, encabezados y pies como Acrobat XI), no la procedencia ni identidad binaria de Acrobat.

El frontoffice (`/convertir-archivos`) permite, por sesión, dos usos de cada herramienta (`public_limit`). Las herramientas se agrupan como en iLovePDF: *Convertir* (PDF a Word, Word a PDF, Imagen a PDF, M4A a MP3), *Organizar* (Unir, Dividir, Rotar, Organizar), *Optimizar* (Comprimir, OCR) y *Editar* (Números de página, Cortar audio). La herramienta elegida queda en la URL (`?herramienta=ocr`).

Cada herramienta es una clase de `app/Conversions` registrada en `config('conversions.tools')` (etiqueta, grupo, extensiones, tamaño máximo y, si acepta varios archivos, cuántos). Valida sus opciones (`options.*` en la página) en `prepare()`, antes de encolar, y hace el trabajo en `process()`, dentro del job `ConvertFile`. Las opciones quedan en la columna `options` del registro. Para agregar una herramienta: crear su clase, registrarla y, si tiene opciones, agregar `resources/views/livewire/partials/conversion-tools/<formato>.blade.php`. El backoffice (`/backoffice/convertir-archivos`) requiere el permiso `navbar.conversiones.visible`; el registro requiere `conversiones.ver_registro`. Los archivos se guardan en el disco privado `local` y se eliminan mediante `conversions:clean`; el registro persiste, con el motor usado, los SHA-256 de entrada y salida y los avisos mostrados.

## PDF a Word

1. **Inspección (Poppler).** `pdfinfo` rechaza PDF protegidos con contraseña, dañados o con más de `CONVERSIONS_PDF_MAX_PAGES` páginas (1500). Referencia: 1.285 páginas tardan unos 80 s y usan unos 2,6 GB de RAM, así que conviene limitar los trabajadores simultáneos según la memoria del servidor. `pdftotext` y `pdfimages` detectan, página por página, las que no tienen texto seleccionable y sí imágenes (escaneadas).
2. **Escaneados.** Si `CONVERSIONS_OCRMYPDF` está configurado se aplica OCR (`--skip-text`, idiomas `CONVERSIONS_OCR_LANGUAGES`, por defecto `spa`) y se avisa de revisar el texto reconocido. Sin OCR, un PDF sin texto se rechaza y uno mixto se convierte avisando qué páginas quedan solo como imagen.
3. **Motor (`CONVERSIONS_PDF_ENGINE`).** Si falla el motor configurado, la conversión falla: no se cambia en silencio a otro motor.
   - `pdf2docx` (predeterminado): `scripts/pdf_to_docx.py` reconstruye párrafos, tablas e imágenes. Antes, el contenido repetido en la misma posición dentro del 20 % superior o inferior de la página (membrete, logo, dirección, QR y texto de validación) pasa al encabezado o pie de Word como objetos anclados a la página, como hace Acrobat XI. Para eso basta con que aparezca en al menos tres páginas, o en la mitad de un documento corto. Así, en un PDF unido cada documento conserva su propio pie. Cada página recibe solo lo que tenía, de modo que una última página sin membrete queda sin él. Los números de página impresos, es decir, un número (opcionalmente después de «Página» o «Pág.») en la misma franja y alineación que aumenta de uno en uno, pasan al pie como campo `PAGE` de Word, numerado desde el valor del PDF. El margen inferior de cada página nunca se agranda respecto del que calcula `pdf2docx` y se calcula con la altura de esa misma página, para que el número de página y las páginas de otro tamaño no se desborden. El interlineado se escribe **exacto**, medido en el PDF: así una fuente sustituta (por ejemplo, Bookman Old Style → URW Bookman en Linux) no cambia la altura de las líneas ni hace que el contenido pase a otra hoja. Si se aumenta el tamaño de letra en Word, hay que ajustar también el interlineado. Las firmas y anotaciones se conservan como imagen de su apariencia en el mismo lugar, así que su texto no es editable. Esto incluye los recuadros de firma electrónica aplanados, que se reconocen por un marco con imagen y texto y una franja de color en su borde izquierdo; sin este tratamiento, `pdf2docx` los convertía en una tabla con la franja pintada sobre toda la celda. Las firmas lado a lado forman una sola imagen, y su texto (nombre, cargo, fecha) queda como texto alternativo.
   - `libreoffice`: `writer_pdf_import` produce cuadros de texto posicionados. `LibreOfficeDocxNormalizer` corrige el desplazamiento de unos 28 pt anclando los objetos a la página y lleva detrás del texto solo los fondos **blancos**, para no destapar máscaras o tachaduras de color.
4. **Validación.** El DOCX debe ser un ZIP/XML válido con texto. Se entrega tal cual lo produjo el motor y su SHA-256 queda registrado.

Medición con `AA 401-2026.pdf` (5 páginas), renderizado con LibreOffice 26.2 y comparado palabra por palabra con el PDF:

| Salida | Páginas | Desfase vertical mediano | Estructura |
|---|---|---|---|
| Acrobat XI | 5 | −0,4 pt | párrafos, encabezado/pie |
| LibreOffice sin normalizar | 5 | +28,7 pt | 182 cuadros de texto; contenido tapado en la página 5 |
| LibreOffice normalizado | 5 | +0,4 pt | cuadros de texto |
| pdf2docx + encabezado/pie | 5 | −2,1 pt | párrafos, tablas, encabezado/pie |

Con `RIT 222-2026.pdf` (8 páginas tamaño Oficio, membrete ausente en la última), la versión anterior producía 15 páginas porque el membrete quedaba en el cuerpo y desbordaba cada hoja. Ahora produce 8, con todas las palabras en la misma página que en el PDF y un desfase vertical mediano de −2,1 pt.

Con `90-2023 final.pdf` (1.285 páginas; ver [plan-paginacion-firmas-pdf-word.md](plan-paginacion-firmas-pdf-word.md)), la paginación depende sobre todo de tener las fuentes del documento, Garamond y Calibri. Sin ellas, el texto es un 23,5 % más ancho y no hay corrección posible desde la conversión.

No se probó en Microsoft Word. Diferencias conocidas frente a Acrobat: listas como texto «1.» en vez de numeración de Word, texto de firmas como imagen y pequeñas variaciones de cortes de línea por sustitución de fuentes (Arial/Helvetica → Liberation Sans/Nimbus Sans en Linux). Instalar las fuentes del documento en el servidor reduce esas variaciones.

## Herramientas de páginas (unir, dividir, rotar, organizar, numerar, imágenes)

Siguen los modos de iLovePDF. Las procesa `scripts/pdf_pages.py` con PyMuPDF, del mismo entorno que el motor PDF a Word, sin volver a renderizar las páginas.

Las firmas electrónicas (campos de firma) y las anotaciones no forman parte del contenido de la página: se dibujan encima. Antes de copiar las páginas, su apariencia se integra a la página (`bake`). Así el recuadro de firma se sigue viendo y los enlaces se conservan. Con Poppler, `pdfseparate` borraba la firma (caso `RIT 222-2026.pdf`, página 8) y `pdfunite` borraba los enlaces. Una firma deja de ser verificable criptográficamente en cuanto se reescriben las páginas, con cualquier herramienta. Por eso el resultado avisa que se valide con el código de verificación (QR) del documento. Comprimir y OCR también aplanan primero.

- **Unir.** De 2 a 20 PDF, cada uno de hasta 20 MB y en total hasta 100 MB. Los archivos se suben de uno en uno, así que el total no depende de `post_max_size`. Al llegar cada archivo se lee su número de páginas, de modo que un PDF protegido o dañado se rechaza en ese momento. El orden se cambia con los botones subir y bajar, o con «Ordenar por nombre». El resultado es `<primer archivo>_unido.pdf`, y el SHA-256 de cada entrada queda en `options.inputs`.
- **Dividir.** Un PDF. Cuando termina de subir se muestra su número de páginas y los rangos se comprueban contra él antes de encolar el trabajo.
  - *Dividir por rango → personalizados*: `1-3, 5, 8-10`. Cada rango genera un PDF, o uno solo si se marca «Unir todos los rangos».
  - *Dividir por rango → fijos*: archivos de N páginas; el último puede tener menos.
  - *Extraer páginas → todas*: un PDF por página.
  - *Extraer páginas → seleccionar*: las páginas indicadas, por separado o unidas en un PDF.

  Si el resultado es un solo PDF se entrega como `<nombre>_dividido.pdf`. Si son varios, como `<nombre>_dividido.zip`, con partes llamadas `<nombre>_<rango>.pdf`.
- **Rotar.** Todas las páginas o un rango (`1, 3-5`), 90°, 180° o 270°. Solo cambia la rotación de la página; el contenido no se vuelve a dibujar.
- **Organizar.** Las miniaturas se dibujan **en el navegador** con pdf.js (`pdfjs-dist`, cargado solo en esta herramienta), así que el servidor no genera imágenes. Se arrastran para reordenar, y cada una se puede rotar o quitar. La página envía el plan (`[{page, rotation}]`) y el servidor lo valida contra el número de páginas real. Hasta 300 páginas, para cuidar la memoria del navegador.
- **Números de página.** Seis posiciones, margen, tamaño, formato (`1`, `Página 1`, `Página 1 de N`, `Fs. 1` para foliar), primer número y rango de páginas (por ejemplo, desde la 2 para no numerar la carátula). Respeta la rotación de cada página, así que el número queda derecho en escaneos girados.
- **Imagen a PDF.** Hasta 50 JPG o PNG en el orden elegido. Tamaño de página (ajustar a la imagen, Oficio de 216 × 330 mm, Carta o A4), orientación y margen. Un PDF con todas o uno por imagen (ZIP). Los JPEG se insertan sin recomprimir, y las fotos del celular se enderezan según su orientación EXIF (Pillow). Opcionalmente se les aplica OCR.

## OCR: PDF escaneado a PDF con texto buscable

OCRmyPDF (Tesseract, español) agrega una capa de texto invisible y seleccionable sobre la imagen original de cada página, que no se vuelve a dibujar. Opciones: idioma (español, o español e inglés), enderezar páginas (`--rotate-pages --deskew`), «Rehacer el OCR» (`--redo-ocr`, para escaneos con un OCR malo; si no se marca, las páginas que ya tienen texto no se tocan) y PDF/A.

- Un PDF que ya tiene texto en todas sus páginas se rechaza **antes de encolarlo**, sin consumir el cupo, salvo que se marque «Rehacer el OCR».
- Hasta `CONVERSIONS_OCR_MAX_PAGES` páginas (1000) y 50 MB. Medido con 4 núcleos (`CONVERSIONS_OCR_JOBS`): 40 páginas reales a 300 ppp en 42 s (unos 1 s por página y 380 MB de RAM). 1000 páginas tardan unos 18 minutos. Un escaneo de 1000 páginas en escala de grises a 300 ppp pesa unos 120 MB, más que el límite de 50 MB. Caben escaneos en blanco y negro (CCITT/JBIG2, unos 30–50 KB por página) o de menor resolución. Subir más requiere aumentar el límite de Livewire y de PHP.
- El job de OCR tiene su propio tiempo límite: `CONVERSIONS_OCR_TIMEOUT` (1800 s) más 120 s. Por eso `DB_QUEUE_RETRY_AFTER` vale 2100 por defecto y siempre debe ser mayor. El aviso del resultado indica en qué páginas se reconoció texto y en cuáles no se encontró.
- Sin `CONVERSIONS_OCRMYPDF`, la herramienta OCR no aparece y PDF a Word rechaza los PDF escaneados. Con la variable, PDF a Word también aplica OCR a las páginas escaneadas.

## Comprimir y Word a PDF

- **Comprimir.** Ghostscript `pdfwrite` con tres niveles: extrema (`/screen`, 72 ppp), recomendada (`/ebook`, 150 ppp) y baja (`/printer`, 300 ppp). Si el resultado no ahorra al menos un 5 %, se entrega el original sin cambios, con su firma intacta. El aviso muestra el tamaño antes y después. Se verifica que no cambie el número de páginas.
- **Word a PDF.** LibreOffice (DOC, DOCX, ODT, RTF) con un perfil propio por trabajo. Si el servidor no tiene las fuentes del documento, la paginación puede cambiar: conviene instalar las fuentes que usa el tribunal.

## Cortar audio

Editor al estilo de Audacity (plan en [Implementacion_CortarAudios.md](Implementacion_CortarAudios.md); rediseño de la interfaz en [Implementacion_CortarAudios_UX.md](Implementacion_CortarAudios_UX.md)). Al subir el archivo, `AudioInspector` mide su duración con `ffprobe` y calcula la forma de onda en el servidor: hasta 50 picos por segundo y como máximo 200.000 puntos, que viajan una sola vez en el evento `audio-loaded`. El navegador dibuja la onda con `wavesurfer.js` 7.12.12 y reproduce el archivo local. No lo decodifica ni lo descarga del servidor, así que un audio de 50 MB ocupa unos 10 MB de memoria en la pestaña.

**Interfaz.** Elegir «Cortar audio» (o abrir `?herramienta=audiocut`) abre un modal de casi toda la pantalla, que ocupa todo el celular, con cuatro pasos numerados. Un paso solo se habilita cuando el anterior está listo, y el riel de arriba muestra lo decidido en cada paso terminado (un clic vuelve a él):

1. **Sube el audio:** nombre (solo en el sitio público) y archivo, que se puede arrastrar. Al terminar el análisis se pasa solo al paso 2.
2. **Marca los fragmentos:** la onda sobre fondo oscuro, con reloj y regla de tiempo, y la tira «Así quedará tu audio» con la duración final.
3. **Elige qué hacer:** tres tarjetas (cada una con su duración resultante) y, en «Opciones avanzadas», fundido, formato y volumen.
4. **Corta y descarga:** resumen, estado del trabajo y descarga sin salir del modal; después se puede «Volver a marcar este audio» o «Cortar otro audio».

Cerrar el modal (botón o Escape) **conserva el avance**; «Abrir el cortador» lo retoma donde quedó. Un error de validación del servidor (por ejemplo, falta el nombre) lleva al paso que lo contiene. Con `prefers-reduced-motion` las transiciones son instantáneas.

Detalles técnicos que importan al tocar el código:

- El `<dialog>` lleva `wire:ignore.self` y el bloque «Opciones avanzadas» también: el componente tiene `wire:poll.5s`, y cada re-render le quitaría al diálogo su atributo `open` y cerraría ese bloque.
- Después de un re-render de Livewire, `$refs` y `$root` de Alpine vuelven vacíos durante un momento, y el foco debe moverse justo entonces. Por eso `dialogEl()` y `heading()` en `resources/js/app.js` buscan por id en el documento. No volver a `$refs` para eso.
- El modal se renderiza una vez por página, así que sus ids (`audio-cutter`, `ac-h1`…`ac-h4`, `conversion-file`) son únicos.
- «Cortar otro audio» y «Volver a marcar» se resuelven desde Alpine, esperando la respuesta de Livewire, porque esos botones desaparecen con el re-render.
- El número que muestra el cliente («Así quedará tu audio», tarjetas) lo calcula `plan()` en `audio-editor.js` con la misma regla que `AudioCutTool::spans()`; si una cambia, cambia la otra.

- **Fragmentos:** se marcan arrastrando sobre la onda, con «Agregar un fragmento en el cursor» o escribiendo inicio y fin (`h:mm:ss.mmm`, `m:ss.mmm` o segundos). Si se escribe un inicio posterior al fin, el fragmento se desplaza conservando su duración. Se puede deshacer y rehacer, y hay atajos de Audacity (Espacio, `Ctrl+Z`, `Ctrl+I`, `Supr`, `[` `]`, `Ctrl+1`/`Ctrl+3`; la lista completa está en «Atajos de teclado»). Máximo: 200 fragmentos.
- **Modos:** *Conservar lo marcado* (une las regiones, o entrega cada una en un archivo aparte; varias van en un ZIP), *Quitar lo marcado* y *Silenciar lo marcado* (misma duración). Opcionalmente: fundido de 0,1, 0,5 o 2 s (en las uniones, o al inicio y fin de cada archivo aparte) y normalización de volumen (`loudnorm`, conservando la frecuencia original).
- **Servidor:** no confía en el navegador. `prepare()` vuelve a medir el audio y valida, ordena y une las regiones. `process()` siempre recodifica: `-c copy` cortaría en límites de trama y dejaría clics. Después mide cada archivo producido y falla si su duración difiere más de 0,15 s de lo esperado.
- **Formatos:**
  - **Entrada:** MP3, M4A, WAV, OGG, OPUS, FLAC, AAC, WMA y AMR. `oga` y `asf` son alias aceptados que no se muestran: el sistema detecta los WMA como ASF.
  - **Salida:** MP3 (192 kb/s), M4A (AAC 192 kb/s), WAV (16 bits), OGG (Vorbis q5) y FLAC.
  - El navegador no reproduce WMA ni AMR. Con esos formatos se avisa, pero la onda y el corte funcionan igual.
- **Límites:** 50 MB y 4 horas (`CONVERSIONS_AUDIO_MAX_SECONDS`). El tiempo del job es `max(360, duración / 10 + 120)` s: 1.560 s para 4 horas, por debajo de `DB_QUEUE_RETRY_AFTER`.
- **Mediciones** (equipo de desarrollo, FFmpeg 7.0.2):
  - Onda de 1 hora de audio: 1,83 s.
  - Corte de esa hora, recodificado a MP3: 16,46 s.
  - MP3 de 48 MB (50 min), de la subida a la onda dibujada: 4,9 s.
- **Corregido:** `dragToSeek` de wavesurfer 7 le quita el arrastre a la selección de regiones, así que no se usa. Un clic sigue moviendo el cursor.

## Requisitos del servidor

- Python 3 con el entorno de los motores (PyMuPDF, pdf2docx, Pillow, OCRmyPDF), fijado en `scripts/requirements.lock.txt`. Se instala en `.venv-conversions` y se configura `CONVERSIONS_PYTHON=<ruta absoluta>/.venv-conversions/bin/python` y `CONVERSIONS_OCRMYPDF=<ruta absoluta>/.venv-conversions/bin/ocrmypdf`.
  - **Con internet:** `python3 -m venv .venv-conversions && .venv-conversions/bin/pip install -r scripts/requirements.lock.txt`.
  - **Sin internet (producción):** `scripts/build-offline-bundle.sh` arma en un equipo con internet un paquete con los paquetes Python, Tesseract (con español), Ghostscript, Poppler y, con `--full`, LibreOffice y FFmpeg, para Linux (Ubuntu) o Windows. Los pasos están en [despliegue-herramientas-pdf.md](despliegue-herramientas-pdf.md), y `php artisan conversions:check` verifica el resultado en el servidor.
  - Licencias: PyMuPDF y Ghostscript son AGPL-3.0 (o comerciales de Artifex), OCRmyPDF MPL-2.0 y Tesseract Apache-2.0. Revisar el licenciamiento antes de ofrecer el conversor al público.
- Poppler (`pdfinfo`, `pdftotext`, `pdfimages`; rutas en `CONVERSIONS_PDFINFO`, `CONVERSIONS_PDFTOTEXT`, `CONVERSIONS_PDFIMAGES`). Unir y dividir PDF también requieren el entorno Python de `CONVERSIONS_PYTHON` (PyMuPDF).
- OCR: Tesseract 5 con español (paquetes del sistema `tesseract-ocr` y `tesseract-ocr-spa`; no vienen por pip) y Ghostscript (`CONVERSIONS_GHOSTSCRIPT`, que también usa Comprimir). OCRmyPDF viene en el entorno Python.
- LibreOffice para Word a PDF y, con el filtro `writer_pdf_import`, para PDF a Word si `CONVERSIONS_PDF_ENGINE=libreoffice` (`CONVERSIONS_LIBREOFFICE` si su ejecutable tiene otra ubicación). Cada trabajo usa su propio perfil dentro del directorio temporal privado.
- FFmpeg y FFprobe (vienen juntos en el paquete `ffmpeg` de Ubuntu y en el build *essentials* de Windows) disponibles para el trabajador de cola, con los codificadores `libmp3lame`, `aac`, `pcm_s16le`, `libvorbis` y `flac`. Si sus ejecutables están en otra ubicación: `CONVERSIONS_FFMPEG` y `CONVERSIONS_FFPROBE`.
- Cola de base de datos migrada y trabajador activo (`php artisan queue:work`). Cada job declara su tiempo límite: 360 s, o 1920 s con OCR. `DB_QUEUE_RETRY_AFTER` (2100 s por defecto) debe ser mayor que el más largo. Si `.env` lo define con un valor menor, ajustarlo. En desarrollo, `composer run dev` usa `queue:listen --timeout=0`, porque el valor por defecto de 60 s cortaba las conversiones largas. Programar `php artisan schedule:run` cada minuto en producción para limpiar los archivos expirados.
- PHP y servidor web con `upload_max_filesize` y `post_max_size` superiores a 50 MB (recomendado: `64M`). Livewire acepta hasta 50 MB. Si PHP tiene un límite menor (el valor de fábrica es 2 MB), descarta el archivo antes de que llegue a Laravel. Cada herramienta tiene su máximo por archivo (20 MB o 50 MB; ver `config('conversions.tools')`). El formulario muestra el límite efectivo, el menor entre el de la herramienta y el de PHP, y avisa antes de subir un archivo que lo supere. El límite se ajusta en el `php.ini` que usa cada servidor:
  - `php artisan serve` / `composer run dev`: el `php.ini` de la CLI (`php --ini`; en Ubuntu, `/etc/php/8.x/cli/php.ini`). Sin cambiarlo, se puede lanzar `PHP_INI_SCAN_DIR=":/ruta/con/uploads.ini" composer run dev`, donde `uploads.ini` contiene las dos líneas.
  - XAMPP: `C:\xampp\php\php.ini` y reiniciar Apache. `docs/documentos-xampp.md` indica 25M; el conversor necesita 64M.
  - PHP-FPM: el `php.ini` de FPM y reiniciar el servicio.

Se recomienda probar la calidad del DOCX con PDFs representativos del tribunal, abriéndolos en el Word que usan los funcionarios. Después de desplegar: `php artisan migrate`, `npm run build` (o subir `public/build`, que incluye pdf.js) y reiniciar los trabajadores de cola (`php artisan queue:restart`). Las pruebas (`tests/Feature/FileConversionTest.php` y `PdfToolsTest.php`, con fixtures en `tests/Helpers/conversions.php`) que necesitan Python, OCRmyPDF o Tesseract se omiten si no están instalados. Las pruebas leen `.env.testing`, así que hay que definirlo ahí o al ejecutarlas: `CONVERSIONS_PYTHON=$PWD/.venv-conversions/bin/python ./vendor/bin/pest tests/Feature/FileConversionTest.php tests/Feature/PdfToolsTest.php`. Si no, la salida muestra «deprecated» (por el aviso de PHP 8.5 en `config/database.php`) y oculta que se omitieron.
