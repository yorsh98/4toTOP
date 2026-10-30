# Despliegue de las herramientas PDF en un servidor sin internet

Instala y configura lo que necesitan las herramientas de `/convertir-archivos` (OCR, comprimir, Word a PDF, imagen a PDF, números de página, rotar, organizar, unir, dividir, PDF a Word y M4A a MP3). Qué hace cada una está en [file-conversions.md](file-conversions.md).

El servidor no tiene acceso a pip, apt ni npm, así que todo se prepara antes en un equipo con internet y se lleva en un paquete. **Lo probado:** el paquete de Linux (Ubuntu 24.04) se instaló en un contenedor sin red, con OCR real y todas las comprobaciones en verde. El de Windows se armó y se verificó con sumas SHA-256, pero **no se probó en un Windows**: ver «Si el servidor es Windows».

## 1. Preparar el paquete (equipo con internet)

Necesita el repositorio, `pip` y, para Linux, Docker. Primero hay que saber qué tiene el servidor:

| Dato | Windows | Linux |
|---|---|---|
| Sistema | Windows de 64 bits | `lsb_release -rs` (por ejemplo `24.04`) y `uname -m` (debe ser `x86_64`) |
| Python | no importa: el paquete trae el suyo (3.13) | `python3 --version` (por ejemplo `3.12`) |

```bash
# Windows (unos 800 MB con --full)
scripts/build-offline-bundle.sh windows 3.13 --full

# Linux (unos 255 MB)
scripts/build-offline-bundle.sh linux 3.12 24.04 --full
```

`--full` agrega LibreOffice (Word a PDF) y FFmpeg con FFprobe (M4A a MP3, Cortar audio). Omítelo si el servidor ya los tiene: `conversions:check` lo dice.

El resultado queda en `build/offline-<sistema>-py<versión>.zip` (Windows) o `.tar.gz` (Linux). Dentro hay `wheels/` con los 47 paquetes Python, `requirements.lock.txt`, `SHA256SUMS.txt` y, según el sistema, `installers/` y `tessdata/` o `debs/`. Copia ese archivo al servidor, junto con el repositorio ya actualizado y su `public/build` (ver el paso 5).

## 2. Qué se instala y para qué

| Componente | Lo usan | Cómo llega al servidor |
|---|---|---|
| Entorno Python con PyMuPDF, pdf2docx, Pillow y OCRmyPDF | todas las herramientas PDF | `wheels/`, con `pip --no-index` |
| Poppler (`pdfinfo`, `pdftotext`, `pdfimages`) | todas las herramientas PDF | `.deb` / `Release-*.zip` |
| Tesseract 5 con español y `osd` | OCR, Imagen a PDF con OCR, PDF a Word de escaneos | `.deb` / instalador y `tessdata/` |
| Ghostscript | Comprimir, OCR | `.deb` / instalador |
| LibreOffice | Word a PDF | solo con `--full` |
| FFmpeg y FFprobe | M4A a MP3, Cortar audio | solo con `--full` |

`spa` es el idioma del reconocimiento y `osd` permite enderezar páginas giradas.

## 3. Instalar en Linux (Ubuntu)

Como `root`, en la carpeta donde se extrajo el paquete (`tar -xzf offline-linux-py312.tar.gz && cd offline-linux-py312`):

```bash
sha256sum -c SHA256SUMS.txt                       # todo debe decir OK
export DEBIAN_FRONTEND=noninteractive
dpkg -i debs/*.deb                                # sin red; no uses apt para esto
python3 -m venv <RUTA>/.venv-conversions
<RUTA>/.venv-conversions/bin/pip install --no-index --find-links wheels -r requirements.lock.txt
tesseract --list-langs                            # debe listar eng, osd, spa
```

`<RUTA>` es la raíz del proyecto. Si `dpkg` avisa que algo ya está instalado, es normal: omite lo que el servidor tiene. Si `python3-venv` queda sin configurar, el `python3` del servidor no es de la versión indicada al armar el paquete.

## 4. Configurar `.env`

Linux (`<RUTA>` = raíz del proyecto, por ejemplo `/var/www/4toTOP`):

```dotenv
CONVERSIONS_PYTHON=<RUTA>/.venv-conversions/bin/python
CONVERSIONS_OCRMYPDF=<RUTA>/.venv-conversions/bin/ocrmypdf
CONVERSIONS_TESSERACT=tesseract
CONVERSIONS_OCR_JOBS=4
DB_QUEUE_RETRY_AFTER=2100
```

Sin `CONVERSIONS_OCRMYPDF`, la herramienta OCR no aparece y PDF a Word rechaza los PDF escaneados. Opcionales, con su valor por defecto: `CONVERSIONS_OCR_MAX_PAGES=1000`, `CONVERSIONS_OCR_TIMEOUT=1800`, `CONVERSIONS_OCR_LANGUAGES=spa`, `CONVERSIONS_GHOSTSCRIPT=gs`, `CONVERSIONS_LIBREOFFICE=libreoffice`, `CONVERSIONS_FFMPEG=ffmpeg`, `CONVERSIONS_FFPROBE=ffprobe`, `CONVERSIONS_AUDIO_MAX_SECONDS=14400`, `CONVERSIONS_PDFINFO`, `CONVERSIONS_PDFTOTEXT`, `CONVERSIONS_PDFIMAGES`.

- **`CONVERSIONS_OCR_JOBS`:** núcleos que usa cada OCR. Con 4, 40 páginas reales tardaron 42 s y 1000 páginas toman unos 18 minutos. No lo pongas por encima de los núcleos del servidor.
- **`DB_QUEUE_RETRY_AFTER`:** debe ser **mayor** que el trabajo más largo (1920 s con OCR). Si `.env` ya lo define con 420 u otro valor menor, cámbialo: con un valor menor, la cola da por perdido un OCR largo y lo vuelve a lanzar.
- Si el servidor tiene otro `python3` global, `CONVERSIONS_PYTHON` **debe** apuntar al del entorno `.venv-conversions`: ese es el que tiene PyMuPDF.

## 5. Actualizar la aplicación

Desde la raíz del proyecto, con el código nuevo:

```bash
php artisan migrate --force          # agrega la columna options a file_conversions; no pierde datos
php artisan optimize:clear
```

La interfaz usa una dependencia nueva, `pdfjs-dist`, que Vite empaqueta. Como el servidor no tiene npm, **compila en el equipo con internet** (`npm ci && npm run build`) y copia la carpeta `public/build` completa. `public/build` está en `.gitignore`, así que no viaja con el repositorio. Si falta, «Organizar PDF» no muestra las miniaturas.

Después, reinicia **siempre** los trabajadores de la cola: guardan el código antiguo en memoria y, con la configuración anterior, fallan con errores como `First element must contain a non-empty program name`.

```bash
php artisan queue:restart
```

## 6. Mantener vivos el trabajador de cola y el programador

Las conversiones solo avanzan si hay un trabajador de cola corriendo, y los archivos vencidos se limpian con el programador (`conversions:clean`, cada hora).

```ini
# /etc/systemd/system/4toTOP-queue.service
[Unit]
Description=Cola de 4toTOP
After=network.target mysql.service

[Service]
User=www-data
WorkingDirectory=<RUTA>
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=1
Restart=always
# El servicio no hereda tu PATH: OCRmyPDF busca tesseract y gs aquí.
Environment=PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

[Install]
WantedBy=multi-user.target
```

```bash
systemctl daemon-reload && systemctl enable --now 4toTOP-queue
# cron del usuario del servidor web:  * * * * * cd <RUTA> && php artisan schedule:run >> /dev/null 2>&1
```

El usuario del servicio debe poder escribir en `storage/` y leer `.venv-conversions`. No hace falta `--timeout`: cada trabajo trae su límite (360 s, o 1920 s con OCR).

## 7. PHP: límites de subida

`upload_max_filesize` y `post_max_size` en **64M** (las herramientas aceptan hasta 50 MB por archivo), en el `php.ini` que usa el servidor web (PHP-FPM o Apache), no el de la consola; luego reinicia el servicio. `conversions:check` solo ve el de la consola. Para el del servidor web, abre `/convertir-archivos`: el formulario muestra el límite real bajo el selector de archivo.

## 8. Verificar

```bash
php artisan conversions:check
```

Revisa cada programa, **ejecuta un OCR de verdad** sobre un escaneo generado (prueba OCRmyPDF, Tesseract con español y Ghostscript juntos) y comprueba `retry_after` y el límite de PHP. Debe terminar con «Todo listo» y código de salida 0; si falla, indica qué falta y para qué herramienta. Si no instalaste FFmpeg o LibreOffice a propósito, solo esas filas aparecerán en rojo.

Prueba manual, en este orden:
1. `/convertir-archivos?herramienta=ocr` con un PDF escaneado de 2 o 3 páginas: el resultado se puede buscar con Ctrl+F.
2. Un PDF firmado electrónicamente (por ejemplo `RIT 222-2026.pdf`) dividido: la firma de la última página sigue visible.
3. «Organizar PDF»: aparecen las miniaturas y se pueden mover.

## 9. Si el servidor es Windows (XAMPP)

> Sin probar en Windows. Las rutas de abajo son las predeterminadas de cada instalador; ajústalas si eligieron otras.

1. Extrae `offline-windows-py313.zip` y verifica `SHA256SUMS.txt` (`certutil -hashfile <archivo> SHA256`).
2. Instala, en este orden, con `installers\`: **Python 3.13** (marca «Add python.exe to PATH»), **Tesseract** (carpeta `C:\Program Files\Tesseract-OCR`) y **Ghostscript**. Si el idioma español no se marcó al instalar Tesseract, copia `tessdata\spa.traineddata` y `tessdata\osd.traineddata` a `C:\Program Files\Tesseract-OCR\tessdata`. Con `--full`, instala también el `.msi` de LibreOffice.
3. Descomprime `Release-*.zip` (Poppler) en `C:\poppler` y, con `--full`, FFmpeg en `C:\ffmpeg`.
4. Agrega al **PATH del sistema** (no solo del usuario, para que lo vea el servicio de la cola) las carpetas `C:\Program Files\Tesseract-OCR`, `C:\Program Files\gs\gs10.xx.x\bin` y `C:\poppler\Library\bin`, y reinicia la sesión.
5. Crea el entorno:
   ```bat
   py -3.13 -m venv <RUTA>\.venv-conversions
   <RUTA>\.venv-conversions\Scripts\pip install --no-index --find-links wheels -r requirements.lock.txt
   ```
6. `.env`, con barras normales:
   ```dotenv
   CONVERSIONS_PYTHON=D:/4toTOP/.venv-conversions/Scripts/python.exe
   CONVERSIONS_OCRMYPDF=D:/4toTOP/.venv-conversions/Scripts/ocrmypdf.exe
   CONVERSIONS_GHOSTSCRIPT=C:/Program Files/gs/gs10.08.0/bin/gswin64c.exe
   CONVERSIONS_PDFINFO=C:/poppler/Library/bin/pdfinfo.exe
   CONVERSIONS_PDFTOTEXT=C:/poppler/Library/bin/pdftotext.exe
   CONVERSIONS_PDFIMAGES=C:/poppler/Library/bin/pdfimages.exe
   CONVERSIONS_LIBREOFFICE=C:/Program Files/LibreOffice/program/soffice.exe
   CONVERSIONS_FFMPEG=C:/ffmpeg/bin/ffmpeg.exe
   CONVERSIONS_FFPROBE=C:/ffmpeg/bin/ffprobe.exe
   CONVERSIONS_TESSERACT="C:/Program Files/Tesseract-OCR/tesseract.exe"
   CONVERSIONS_OCR_JOBS=4
   DB_QUEUE_RETRY_AFTER=2100
   ```
   En Windows, `gs` no existe: `CONVERSIONS_GHOSTSCRIPT` debe apuntar a `gswin64c.exe`.
7. `php.ini` de XAMPP: `upload_max_filesize = 64M` y `post_max_size = 64M` (en vez de los 25M de [documentos-xampp.md](documentos-xampp.md)), y reinicia Apache.
8. En Windows, `--timeout` no sustituye los límites de los procesos: PHP no dispone de PCNTL. Mantén `DB_QUEUE_RETRY_AFTER` por encima de 1920 s y ajusta `--timeout` al máximo configurado para los trabajos.

9. El trabajador de la cola debe correr como servicio o tarea programada que **se inicie con Windows** (por ejemplo con NSSM: `nssm install 4toTOP-queue php.exe artisan queue:work --sleep=3 --tries=1 --timeout=1920`, con el directorio del proyecto como carpeta de inicio), y el programador como tarea cada minuto: `php artisan schedule:run`.
9. Sigue con los pasos 5 y 8 de arriba (`migrate`, `public/build`, `queue:restart`, `conversions:check`).

La primera vez, `conversions:check` es la prueba de que PyMuPDF, OCRmyPDF y Tesseract funcionan en ese Windows; si la fila de OCR falla, el detalle dice el motivo.

## 10. Volver atrás

Las herramientas nuevas no cambian datos existentes: `options` es una columna nula para las conversiones anteriores. Para revertir, despliega el código anterior y ejecuta `php artisan migrate:rollback --step=1` (borra solo esa columna) y `php artisan queue:restart`. Los programas instalados pueden quedarse.
