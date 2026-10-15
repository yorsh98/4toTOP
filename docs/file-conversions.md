# Conversión de archivos

El frontoffice (`/convertir-archivos`) permite dos PDF y dos M4A por sesión. El backoffice (`/backoffice/convertir-archivos`) requiere el permiso `navbar.conversiones.visible`; el registro requiere `conversiones.ver_registro`. Los archivos se guardan en el disco privado `local` y se eliminan mediante `conversions:clean`; el registro persiste.

## Requisitos del servidor

- FFmpeg con `libmp3lame` disponible para el trabajador de cola (`CONVERSIONS_FFMPEG` si su ejecutable tiene otra ubicación).
- LibreOffice con el filtro `writer_pdf_import` disponible para el trabajador de cola (`CONVERSIONS_LIBREOFFICE` si su ejecutable tiene otra ubicación). La importación intenta conservar la disposición, imágenes y texto editable, pero PDF y Word tienen estructuras distintas: comprobar el DOCX antes de usarlo. Los PDF escaneados sin OCR no producen texto editable. Cada trabajo usa su propio perfil de LibreOffice dentro del directorio temporal privado.
- Cola de base de datos migrada y trabajador activo (`php artisan queue:work --timeout=360`); el valor predeterminado de `DB_QUEUE_RETRY_AFTER` es 420 segundos y debe ser superior al tiempo máximo del trabajo. Si ya existe una configuración explícita de `DB_QUEUE_RETRY_AFTER`, ajustarla. Programar `php artisan schedule:run` cada minuto en producción para limpiar los archivos expirados.
- PHP y servidor web con `upload_max_filesize` y `post_max_size` superiores a 50 MB. Livewire acepta hasta 50 MB y la validación del conversor limita PDF a 20 MB y M4A a 50 MB.

Se recomienda probar la calidad del DOCX con PDFs representativos del tribunal. Después de desplegar la nueva versión, reiniciar los trabajadores de cola (`php artisan queue:restart`).
