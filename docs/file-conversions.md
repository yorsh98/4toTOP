# Conversión de archivos

> Referencia de calidad: [PDF a Word con Acrobat XI como referencia obligatoria](plan-pdf-word-acrobat-xi.md). El motor actual **no es Adobe**: busca una salida equivalente (párrafos, tablas, encabezados y pies como Acrobat XI), no la procedencia ni identidad binaria de Acrobat.

El frontoffice (`/convertir-archivos`) permite dos PDF y dos M4A por sesión. El backoffice (`/backoffice/convertir-archivos`) requiere el permiso `navbar.conversiones.visible`; el registro requiere `conversiones.ver_registro`. Los archivos se guardan en el disco privado `local` y se eliminan mediante `conversions:clean`; el registro persiste, con el motor usado, los SHA-256 de entrada y salida y los avisos mostrados.

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

## Requisitos del servidor

- Python 3 con el entorno del motor: `python3 -m venv .venv-conversions && .venv-conversions/bin/pip install -r scripts/requirements.txt` y `CONVERSIONS_PYTHON=<ruta absoluta>/.venv-conversions/bin/python`. PyMuPDF se distribuye con licencia AGPL-3.0 o comercial de Artifex: revisar el licenciamiento antes de ofrecer el conversor al público.
- Poppler (`pdfinfo`, `pdftotext`, `pdfimages`; rutas en `CONVERSIONS_PDFINFO`, `CONVERSIONS_PDFTOTEXT`, `CONVERSIONS_PDFIMAGES`).
- Opcional: OCRmyPDF con Tesseract y el idioma español (`apt install ocrmypdf tesseract-ocr-spa`) y `CONVERSIONS_OCRMYPDF=ocrmypdf`.
- LibreOffice con el filtro `writer_pdf_import`, solo si `CONVERSIONS_PDF_ENGINE=libreoffice` (`CONVERSIONS_LIBREOFFICE` si su ejecutable tiene otra ubicación). Cada trabajo usa su propio perfil dentro del directorio temporal privado.
- FFmpeg con `libmp3lame` disponible para el trabajador de cola (`CONVERSIONS_FFMPEG` si su ejecutable tiene otra ubicación).
- Cola de base de datos migrada y trabajador activo (`php artisan queue:work --timeout=360`); el valor predeterminado de `DB_QUEUE_RETRY_AFTER` es 420 segundos y debe ser superior al tiempo máximo del trabajo. Si ya existe una configuración explícita de `DB_QUEUE_RETRY_AFTER`, ajustarla. Programar `php artisan schedule:run` cada minuto en producción para limpiar los archivos expirados.
- PHP y servidor web con `upload_max_filesize` y `post_max_size` superiores a 50 MB (recomendado: `64M`). Livewire acepta hasta 50 MB y la validación del conversor limita PDF a 20 MB y M4A a 50 MB. Si PHP tiene un límite menor (el valor de fábrica es 2 MB), descarta el archivo antes de que llegue a Laravel. El formulario muestra el límite efectivo, el menor entre el del conversor y el de PHP, y avisa antes de subir un archivo que lo supere. El límite se ajusta en el `php.ini` que usa cada servidor:
  - `php artisan serve` / `composer run dev`: el `php.ini` de la CLI (`php --ini`; en Ubuntu, `/etc/php/8.x/cli/php.ini`). Sin cambiarlo, se puede lanzar `PHP_INI_SCAN_DIR=":/ruta/con/uploads.ini" composer run dev`, donde `uploads.ini` contiene las dos líneas.
  - XAMPP: `C:\xampp\php\php.ini` y reiniciar Apache. `docs/documentos-xampp.md` indica 25M; el conversor necesita 64M.
  - PHP-FPM: el `php.ini` de FPM y reiniciar el servicio.

Se recomienda probar la calidad del DOCX con PDFs representativos del tribunal, abriéndolos en el Word que usan los funcionarios. Después de desplegar: `php artisan migrate` y reiniciar los trabajadores de cola (`php artisan queue:restart`). Las pruebas del motor `pdf2docx` se omiten si `CONVERSIONS_PYTHON` no tiene el paquete instalado.
