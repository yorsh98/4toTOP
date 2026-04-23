# Plan de implementacion de NSSM en Windows (XAMPP + Laravel)

Este documento define como habilitar y operar `php artisan queue:work` como servicio de Windows usando NSSM, para que las colas se procesen de forma continua en el servidor.

## 1) Objetivo

- Ejecutar el worker de Laravel como servicio persistente.
- Evitar dependencia de una consola abierta o de una sesion de usuario activa.
- Estandarizar instalacion, validacion, operacion y rollback.

## 2) Alcance

Incluye:

- Instalacion de NSSM.
- Creacion del servicio `LaravelQueueWorker`.
- Configuracion de logs y reinicio automatico.
- Validaciones de funcionamiento.
- Operacion posterior a despliegues.

No incluye:

- Migracion de datos.
- Cambio de arquitectura de colas (ej. pasar de `database` a `redis`).

## 3) Prerrequisitos

Antes de instalar el servicio, validar lo siguiente:

- PHP operativo en servidor (XAMPP), por ejemplo `C:\xampp\php\php.exe`.
- Proyecto desplegado, por ejemplo `C:\xampp\htdocs\4toTOP`.
- `.env` de produccion correcto:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `QUEUE_CONNECTION=database` (o el backend de cola definido para produccion)
- Base de datos disponible y migraciones aplicadas.
- Tablas de cola presentes (`jobs`, `failed_jobs`).
- Permisos de escritura en `storage/logs`.
- Ventana de mantenimiento aprobada para activacion inicial.

## 4) Instalacion de NSSM

1. Descargar NSSM desde su sitio oficial.
2. Descomprimir en una ruta fija, por ejemplo: `C:\nssm\`.
3. Confirmar binario de 64 bits: `C:\nssm\win64\nssm.exe`.

## 5) Crear el servicio del worker

Abrir `cmd` o PowerShell como Administrador y ejecutar (ajustar rutas segun servidor):

```bat
C:\nssm\win64\nssm.exe install LaravelQueueWorker "C:\xampp\php\php.exe" "artisan queue:work --queue=default --sleep=3 --tries=3 --timeout=120 --max-time=3600"
C:\nssm\win64\nssm.exe set LaravelQueueWorker AppDirectory "C:\xampp\htdocs\4toTOP"
C:\nssm\win64\nssm.exe set LaravelQueueWorker Start SERVICE_AUTO_START
C:\nssm\win64\nssm.exe set LaravelQueueWorker AppStdout "C:\xampp\htdocs\4toTOP\storage\logs\queue-worker.log"
C:\nssm\win64\nssm.exe set LaravelQueueWorker AppStderr "C:\xampp\htdocs\4toTOP\storage\logs\queue-worker-error.log"
C:\nssm\win64\nssm.exe set LaravelQueueWorker AppRotateFiles 1
C:\nssm\win64\nssm.exe set LaravelQueueWorker AppRotateOnline 1
C:\nssm\win64\nssm.exe set LaravelQueueWorker AppRotateBytes 10485760
```

Notas:

- `AppDirectory` debe apuntar al directorio donde vive `artisan`.
- `--max-time=3600` fuerza reciclado del proceso cada hora.
- Si el proyecto usa varias colas, ajustar `--queue=high,default,low`.

## 6) Iniciar y verificar el servicio

Iniciar:

```bat
sc start LaravelQueueWorker
```

Verificar estado:

```bat
sc query LaravelQueueWorker
```

Validar logs:

- `storage/logs/queue-worker.log`
- `storage/logs/queue-worker-error.log`
- `storage/logs/laravel.log`

Prueba funcional recomendada:

1. Encolar un job de prueba.
2. Confirmar que aparece en `jobs`.
3. Confirmar que se procesa y desaparece de `jobs`.
4. Confirmar que no entra a `failed_jobs`.

## 7) Cuenta del servicio y permisos

Recomendado:

- Ejecutar el servicio con una cuenta de servicio dedicada (no usuario personal).
- Otorgar permisos minimos a:
  - carpeta del proyecto
  - `storage/`
  - `bootstrap/cache/`
- Verificar acceso a la base de datos desde esa cuenta.

## 8) Operacion diaria y despliegues

En cada despliegue de codigo:

1. Publicar codigo y dependencias.
2. Ejecutar migraciones.
3. Reiniciar workers para tomar el nuevo codigo:

```bat
php artisan queue:restart
```

4. Confirmar que `LaravelQueueWorker` vuelve a estado `RUNNING`.

Checklist post deploy:

- No aumento de jobs en espera por mas de unos minutos.
- `failed_jobs` sin crecimiento anormal.
- Sin errores criticos en `laravel.log`.

## 9) Monitoreo minimo recomendado

- Revisar diariamente conteo de `failed_jobs`.
- Alerta basica cuando el servicio no este en `RUNNING`.
- Rotacion/control de crecimiento de logs.

## 10) Troubleshooting rapido

Si el servicio no inicia:

- Verificar rutas (`php.exe`, `AppDirectory`, proyecto).
- Verificar permisos de cuenta de servicio.
- Ejecutar manualmente desde consola el mismo comando para reproducir error.
- Revisar `queue-worker-error.log` y `laravel.log`.

Si jobs no se procesan:

- Confirmar `QUEUE_CONNECTION` efectivo en entorno.
- Confirmar migraciones y tablas de cola.
- Confirmar que no hay excepciones en jobs (`failed_jobs`).
- Reiniciar worker con `php artisan queue:restart`.

Si hay jobs colgados o muy lentos:

- Ajustar `--timeout`.
- Dividir colas por prioridad.
- Revisar tareas de alto costo (correo masivo, integraciones externas).

## 11) Plan de rollback

Si la activacion falla en produccion:

1. Detener servicio:

```bat
sc stop LaravelQueueWorker
```

2. Volver temporalmente al esquema previo (worker manual o tarea programada).
3. Corregir causa raiz (permisos/rutas/configuracion) y reintentar en ventana controlada.

Si se requiere eliminar servicio:

```bat
C:\nssm\win64\nssm.exe remove LaravelQueueWorker confirm
```

## 12) Comandos de referencia

```bat
sc start LaravelQueueWorker
sc stop LaravelQueueWorker
sc query LaravelQueueWorker
php artisan queue:restart
php artisan queue:work --queue=default --sleep=3 --tries=3 --timeout=120 --max-time=3600
```

---

Resultado esperado: worker de colas estable, automatico y recuperable tras reinicios del servidor y despliegues.
