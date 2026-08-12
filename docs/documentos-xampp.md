# Repositorio de documentos en XAMPP

Los archivos no deben quedar dentro del directorio que se actualiza con el proyecto. La base de datos guarda sus metadatos y la carpeta persistente guarda los binarios.

1. Cree `D:\4toTOP-data\documentos` y otorgue lectura/escritura al usuario con que se ejecuta Apache.
2. Copie una vez el contenido actual de `public\documentos` a esa carpeta. Mantenga las subcarpetas `procedimientos` y `doc`.
3. Si existe `storage\app\public\documentos`, elimine esa carpeta vacía antes de crear el enlace. Desde una consola de Windows, en la raíz del proyecto, cree la junction:
   ```bat
   mklink /J storage\app\public\documentos D:\4toTOP-data\documentos
   ```
4. Si no existe, cree también el enlace público de Laravel:
   ```bat
   php artisan storage:link
   ```
5. En `php.ini` de XAMPP, configure `upload_max_filesize = 25M` y `post_max_size = 25M`. Reinicie Apache.
6. Ejecute `php artisan migrate --force`, limpie cachés con `php artisan optimize:clear` y reinicie Apache.
7. Pruebe subir un PDF y confirme que aparece en `D:\4toTOP-data\documentos` y abre mediante `/storage/documentos/...`.

No incluya `D:\4toTOP-data` ni `storage\app\public\documentos` en Git ni los elimine durante un despliegue.
