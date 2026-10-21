# Conversión de archivos

> Referencia de calidad: [PDF a Word con Acrobat XI como referencia obligatoria](plan-pdf-word-acrobat-xi.md). El motor actual **no es Adobe**: busca una salida equivalente (párrafos, tablas, encabezados y pies como Acrobat XI), no la procedencia ni identidad binaria de Acrobat.

El frontoffice (`/convertir-archivos`) permite dos PDF y dos M4A por sesión. El backoffice (`/backoffice/convertir-archivos`) requiere el permiso `navbar.conversiones.visible`; el registro requiere `conversiones.ver_registro`. Los archivos se guardan en el disco privado `local` y se eliminan mediante `conversions:clean`; el registro persiste, con el motor usado, los SHA-256 de entrada y salida y los avisos mostrados.

## PDF a Word

1. **Inspección (Poppler).** `pdfinfo` rechaza PDF protegidos con contraseña, dañados o con más de `CONVERSIONS_PDF_MAX_PAGES` páginas (200). `pdftotext` y `pdfimages` detectan, página por página, las que no tienen texto seleccionable y sí imágenes (escaneadas).
2. **Escaneados.** Si `CONVERSIONS_OCRMYPDF` está configurado se aplica OCR (`--skip-text`, idiomas `CONVERSIONS_OCR_LANGUAGES`, por defecto `spa`) y se avisa de revisar el texto reconocido. Sin OCR, un PDF sin texto se rechaza y uno mixto se convierte avisando qué páginas quedan solo como imagen.
3. **Motor (`CONVERSIONS_PDF_ENGINE`).** Si falla el motor configurado, la conversión falla: no se cambia en silencio a otro motor.
   - `pdf2docx` (predeterminado): `scripts/pdf_to_docx.py` reconstruye párrafos, tablas e imágenes. Antes, el contenido repetido en la misma posición en todas las páginas (logo, QR y texto de validación) pasa al encabezado o pie de Word como objetos anclados a la página, como hace Acrobat XI. Las firmas y anotaciones se conservan como imagen de su apariencia en el mismo lugar, así que su texto no es editable.
   - `libreoffice`: `writer_pdf_import` produce cuadros de texto posicionados. `LibreOfficeDocxNormalizer` corrige el desplazamiento de unos 28 pt anclando los objetos a la página y lleva detrás del texto solo los fondos **blancos**, para no destapar máscaras o tachaduras de color.
4. **Validación.** El DOCX debe ser un ZIP/XML válido con texto. Se entrega tal cual lo produjo el motor y su SHA-256 queda registrado.

Medición con `AA 401-2026.pdf` (5 páginas), renderizado con LibreOffice 26.2 y comparado palabra por palabra con el PDF:

| Salida | Páginas | Desfase vertical mediano | Estructura |
|---|---|---|---|
| Acrobat XI | 5 | −0,4 pt | párrafos, encabezado/pie |
| LibreOffice sin normalizar | 5 | +28,7 pt | 182 cuadros de texto; contenido tapado en la página 5 |
| LibreOffice normalizado | 5 | +0,4 pt | cuadros de texto |
| pdf2docx + encabezado/pie | 5 | −2,1 pt | párrafos, tablas, encabezado/pie |

No se probó en Microsoft Word. Diferencias conocidas frente a Acrobat: listas como texto «1.» en vez de numeración de Word, texto de firmas como imagen y pequeñas variaciones de cortes de línea por sustitución de fuentes (Arial/Helvetica → Liberation Sans/Nimbus Sans en Linux). Instalar las fuentes del documento en el servidor reduce esas variaciones.

## Requisitos del servidor

- Python 3 con el entorno del motor: `python3 -m venv .venv-conversions && .venv-conversions/bin/pip install -r scripts/requirements.txt` y `CONVERSIONS_PYTHON=<ruta absoluta>/.venv-conversions/bin/python`. PyMuPDF se distribuye con licencia AGPL-3.0 o comercial de Artifex: revisar el licenciamiento antes de ofrecer el conversor al público.
- Poppler (`pdfinfo`, `pdftotext`, `pdfimages`; rutas en `CONVERSIONS_PDFINFO`, `CONVERSIONS_PDFTOTEXT`, `CONVERSIONS_PDFIMAGES`).
- Opcional: OCRmyPDF con Tesseract y el idioma español (`apt install ocrmypdf tesseract-ocr-spa`) y `CONVERSIONS_OCRMYPDF=ocrmypdf`.
- LibreOffice con el filtro `writer_pdf_import`, solo si `CONVERSIONS_PDF_ENGINE=libreoffice` (`CONVERSIONS_LIBREOFFICE` si su ejecutable tiene otra ubicación). Cada trabajo usa su propio perfil dentro del directorio temporal privado.
- FFmpeg con `libmp3lame` disponible para el trabajador de cola (`CONVERSIONS_FFMPEG` si su ejecutable tiene otra ubicación).
- Cola de base de datos migrada y trabajador activo (`php artisan queue:work --timeout=360`); el valor predeterminado de `DB_QUEUE_RETRY_AFTER` es 420 segundos y debe ser superior al tiempo máximo del trabajo. Si ya existe una configuración explícita de `DB_QUEUE_RETRY_AFTER`, ajustarla. Programar `php artisan schedule:run` cada minuto en producción para limpiar los archivos expirados.
- PHP y servidor web con `upload_max_filesize` y `post_max_size` superiores a 50 MB. Livewire acepta hasta 50 MB y la validación del conversor limita PDF a 20 MB y M4A a 50 MB.

Se recomienda probar la calidad del DOCX con PDFs representativos del tribunal, abriéndolos en el Word que usan los funcionarios. Después de desplegar: `php artisan migrate` y reiniciar los trabajadores de cola (`php artisan queue:restart`). Las pruebas del motor `pdf2docx` se omiten si `CONVERSIONS_PYTHON` no tiene el paquete instalado.
