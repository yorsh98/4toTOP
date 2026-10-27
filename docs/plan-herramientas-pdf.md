# Plan: nuevas herramientas PDF (estilo iLovePDF)

**Estado: implementado el 26 de octubre de 2026** (ver [file-conversions.md](file-conversions.md)). Cambios respecto de este plan: el OCR acepta hasta 1000 páginas, con un tiempo límite propio, y los paquetes se instalan sin internet con `scripts/build-offline-bundle.sh` (ver [despliegue-herramientas-pdf.md](despliegue-herramientas-pdf.md)).

Fecha: 26 de octubre de 2026. Amplía `/convertir-archivos`, que ya tiene PDF a Word, M4A a MP3, unir y dividir (ver [file-conversions.md](file-conversions.md)). Fuera de alcance por decisión: censurar PDF, firmar, proteger y desbloquear, traducir y resumen con IA, PDF a Excel y PDF a PowerPoint.

## Herramientas

| Herramienta | `format` | Entrada | Motor | Fase |
|---|---|---|---|---|
| OCR: PDF escaneado a PDF con texto buscable | `ocr` | 1 PDF | OCRmyPDF + Tesseract (`spa`) | 1 |
| Comprimir PDF | `compress` | 1 PDF | PyMuPDF (bake) + Ghostscript | 2 |
| Word a PDF | `word2pdf` | 1 DOC, DOCX, ODT o RTF | LibreOffice | 3 |
| Imagen a PDF | `img2pdf` | 1 a 50 JPG o PNG | PyMuPDF, con OCR opcional | 3 |
| Números de página / foliar | `pagenum` | 1 PDF | PyMuPDF | 4 |
| Rotar PDF | `rotate` | 1 PDF | PyMuPDF | 5 |
| Organizar PDF (ordenar, rotar y eliminar páginas) | `organize` | 1 PDF | pdf.js en el navegador + PyMuPDF | 5 |

Todas reutilizan lo que ya existe: cola `ConvertFile`, `file_conversions` con `options`, límite de `public_limit` usos por sesión y herramienta en el frontoffice, descarga con token, vencimiento a las 24 h, `conversions:clean`, permisos y registro. No se necesita otra migración: `format` es `string(10)` y todos los nombres caben.

Todas las herramientas que reescriben un PDF pasan primero por el mismo `bake` de `scripts/pdf_pages.py`. Así las firmas electrónicas y las anotaciones siguen visibles, que es la lección del caso `RIT 222-2026.pdf`, y el resultado muestra el mismo aviso de firmas no verificables.

## Fase 0: base común

Con 11 herramientas, el `match` de `ConvertFile` y las propiedades de `ConvertsFiles` crecen demasiado. Antes de agregar herramientas:

1. **Registro de herramientas** en `config/conversions.php` (`tools`): etiqueta, ícono, grupo, extensiones aceptadas, límite de tamaño, extensión del resultado y si acepta varios archivos. Reemplaza las listas repetidas en la vista, en el historial (`FileConversionHistoryPage`) y en `FileConversionService::RESULT_EXTENSIONS`.
2. **Un manejador por herramienta** (`app/Conversions/*Tool.php`, con una interfaz común): reglas de validación de las opciones, opciones normalizadas que se guardan en `options` y proceso dentro del job. `ConvertFile` solo carga el registro, llama al manejador, valida la salida y registra el SHA-256. Unir, dividir, PDF a Word y M4A se mueven sin cambiar su comportamiento; los tests actuales lo protegen.
3. **Selector agrupado** como la portada de iLovePDF: *Convertir* (PDF a Word, Word a PDF, Imagen a PDF, M4A a MP3), *Organizar* (Unir, Dividir, Rotar, Organizar), *Optimizar* (Comprimir, OCR) y *Editar* (Números de página). La herramienta elegida queda en la URL (`?herramienta=ocr`, con `#[Url]`) para poder enlazarla directamente.
4. **Script Python único** para operaciones de página: `scripts/pdf_pages.py` suma subcomandos (`bake`, `number`, `rotate`, `organize`, `images`) que comparten `open_baked`.
5. **Pruebas:** documentar y usar `CONVERSIONS_PYTHON` en `.env.testing`. Sin eso, las pruebas de Python se omiten y la salida de Pest muestra «deprecated» en vez de «skipped», por el aviso de PHP 8.5 en `config/database.php`.

## Fase 1: OCR (PDF escaneado a PDF con texto buscable)

**Motor.** OCRmyPDF conserva la imagen original de cada página y agrega una capa de texto invisible y seleccionable; no vuelve a dibujar la página. En este equipo ya están Tesseract 5 con `spa` y Ghostscript 10.06; falta OCRmyPDF. Se instala en el entorno de conversiones (`ocrmypdf` fijado en `scripts/requirements.txt`, versión 17.x; trae `pikepdf` con `qpdf` incluido) y `CONVERSIONS_OCRMYPDF=<venv>/bin/ocrmypdf`. Esa misma variable activa el OCR que PDF a Word ya tiene preparado (`runOcr`), que hoy está apagado. `runOcr` se mueve a un servicio `PdfOcr` que usan las dos herramientas.

**Opciones** (las de iLovePDF y lo que necesita el tribunal):
- *Idioma:* Español (predeterminado) o Español + Inglés (`-l spa+eng`).
- *Páginas:* «solo las que no tienen texto» (`--skip-text`, predeterminado) o «rehacer OCR» (`--redo-ocr`) para escaneos con un OCR malo.
- *Enderezar páginas* (`--rotate-pages --deskew`): activado por defecto. Cambia la imagen de la página, así que se puede desactivar.
- *Guardar como PDF/A* (archivo de largo plazo): desactivado por defecto. Sin esta opción se usa `--output-type pdf` para no tocar la apariencia.

**Flujo del trabajo:**
1. `PdfInspector::inspect` rechaza PDF protegidos, dañados o con más de `ocr_max_pages` páginas, y obtiene las páginas sin texto.
2. Si se eligió «solo sin texto» y todas las páginas ya tienen texto, se rechaza sin consumir el cupo: «El PDF ya tiene texto seleccionable en todas sus páginas. Si el texto está mal reconocido, elige "Rehacer OCR"».
3. `bake` con PyMuPDF. OCRmyPDF se niega a procesar PDF con firma digital (`DigitalSignatureError`), y aplanarla antes conserva su apariencia, igual que al unir y dividir.
4. `ocrmypdf --jobs N --output-type pdf|pdfa ...`, con un tiempo límite de 300 s.
5. Se valida con `pdftotext` que las páginas que no tenían texto ahora lo tengan. El aviso indica en cuáles se reconoció texto y en cuáles no se encontró nada, como páginas en blanco o fotos.

**Límites y rendimiento.** Medido aquí: Tesseract tarda unos **2 s por página a 300 ppp con un núcleo** y usa unos 140 MB. Con `--jobs 4` son unos 0,5 s por página, así que 300 páginas tardan unos 150 s, dentro del `timeout` del job (360 s). Configuración: `ocr_max_pages` = 300 y `ocr_jobs` = 4 (`CONVERSIONS_OCR_JOBS`), que se ajustan según los núcleos del servidor. Hay que medirlo con un expediente escaneado real antes de fijar los valores en producción.

**Pruebas:** un PDF sintético con una página de imagen de texto generada con PyMuPDF (rechazo si ya tiene texto, OCR de la página sin texto, conservación de una página con texto, aviso de firma y PDF/A), y una prueba con OCRmyPDF falso para errores y tiempo límite.

## Fase 2: comprimir PDF

- **Niveles** como en iLovePDF: *extrema* (`/screen`, 72 ppp), *recomendada* (`/ebook`, 150 ppp, predeterminada) y *baja* (`/printer`, 300 ppp).
- **Flujo:** `bake` y después Ghostscript `pdfwrite` con `-dPDFSETTINGS`, `-dDetectDuplicateImages` y `-dSAFER`. Si el resultado no es al menos un 5 % más chico, se entrega el original con el aviso «El PDF ya estaba optimizado».
- **Resultado:** `<nombre>_comprimido.pdf`. El aviso muestra el tamaño antes y después y el porcentaje ahorrado, como iLovePDF.
- **Riesgo:** Ghostscript puede cambiar fuentes o colores en PDF raros. Comprobar que se conserven el texto (`pdftotext`), las páginas y los enlaces. Medir con `90-2023 final.pdf` y un escaneo pesado.

## Fase 3: Word a PDF e imagen a PDF

**Word a PDF.** LibreOffice `--convert-to pdf` con el mismo perfil temporal por trabajo que ya usa `convertWithLibreOffice`. Acepta DOC, DOCX, ODT y RTF, hasta `pdf_max_kb`. Los macros no se ejecutan en modo `--headless`. Riesgo conocido: las fuentes que faltan en el servidor cambian la paginación (ver el plan PDF a Word); instalar las fuentes del tribunal. Excel y PowerPoint a PDF serían el mismo manejador con otras extensiones, si se piden.

**Imagen a PDF.** Varias imágenes subidas de una en una, como en unir, con orden y botones subir y bajar. Opciones de iLovePDF: orientación (vertical u horizontal), tamaño de página (ajustar a la imagen, Carta, Oficio o A4), margen (sin margen, pequeño o grande) y «un PDF con todas» o «un PDF por imagen» (ZIP). PyMuPDF inserta el JPEG sin recomprimirlo. La orientación EXIF de las fotos del celular se corrige con Pillow, que hay que agregar a `requirements.txt` porque hoy no está instalado. Casilla «Hacer el texto buscable (OCR)», que encadena el servicio de la fase 1.

## Fase 4: números de página / foliar

- **Posición:** 6 posiciones (arriba o abajo, a la izquierda, al centro o a la derecha) y margen (pequeño, mediano o grande).
- **Formato:** `{n}`, `Página {n}`, `Página {n} de {total}` y `Fs. {n}` para foliar expedientes. Número inicial configurable y rango de páginas a numerar (desde/hasta, por ejemplo para no numerar la carátula).
- **Implementación:** PyMuPDF `insert_text` con una fuente incluida (Helvetica) y el tamaño elegido. Debe respetar la rotación de la página (`page.rotation`) y su `CropBox`, para que el número quede derecho y dentro del área visible en escaneos rotados.
- **Pruebas:** posiciones en páginas Carta, Oficio y rotadas, formato con total y número inicial distinto de 1.

## Fase 5: rotar y organizar

**Rotar** (formulario simple): todas las páginas o un rango (`1-3, 7`, con el mismo `parseRanges` de dividir) y un ángulo de 90°, 180° o 270°. Se usa `page.set_rotation`, que no vuelve a dibujar la página.

**Organizar** (miniaturas): `pdfjs-dist`, una dependencia npm nueva que se carga solo en esta herramienta, dibuja las miniaturas **en el navegador** a partir del archivo local. Así el servidor no genera imágenes, incluso con PDF de cientos de páginas. El usuario arrastra para reordenar, rota y elimina páginas. Se envía el archivo junto con el plan `[{page, rotation}]`, y el servidor lo valida contra el número de páginas real y lo aplica con `doc.select()` y `set_rotation`. Límite de miniaturas: 300 páginas, para cuidar la memoria del navegador.

## Orden, despliegue y verificación

1. Cada fase se entrega por separado con sus pruebas, `pint --test` y una prueba de punta a punta en el navegador, como se hizo con unir y dividir.
2. **Servidor:** `pip install -r scripts/requirements.txt` en el entorno de conversiones (fases 1 y 3: OCRmyPDF y Pillow), `apt install tesseract-ocr tesseract-ocr-spa ghostscript`, variables `CONVERSIONS_OCRMYPDF` y `CONVERSIONS_OCR_JOBS`, y `php artisan queue:restart` en cada despliegue.
3. **Memoria y CPU:** el OCR usa `ocr_jobs` núcleos por trabajo. Limitar los trabajadores de cola simultáneos según el servidor, igual que con PDF a Word.
4. **Licencias:** PyMuPDF (AGPL-3.0) ya está en uso. OCRmyPDF es MPL-2.0, Tesseract Apache-2.0 y Ghostscript AGPL-3.0. Revisarlas junto con la de PyMuPDF antes de ofrecer la web al público.

## Decisiones pendientes

- Confirmar `ocr_max_pages` = 300 o medir antes con un expediente escaneado real.
- Aprobar la dependencia `pdfjs-dist` para organizar (fase 5). La alternativa es generar miniaturas en el servidor, que es más lento y requiere servir imágenes temporales.
- Saber si el servidor de producción tiene acceso a internet para `pip install`. Si no, preparar los paquetes (wheels) por adelantado.
