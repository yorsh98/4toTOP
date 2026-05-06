# Habilitacion de ausentismo diario 08:03

Este documento deja operativo el envio diario de notificaciones de ausentismo en Laravel 13.

## Objetivo

- Disparar `notificaciones:ausentismo-diario` todos los dias a las 08:03 (`America/Santiago`).
- Encolar y procesar los correos por cola `database`.
- Dejar monitoreo minimo y rutina de recuperacion.

## Estado esperado

- Scheduler ejecutandose cada minuto.
- Worker de cola activo de forma persistente.
- `NOTIFICATIONS_V2_ENABLED=true` en entorno destino.
- Registros diarios en `notification_dispatches` con `event_key` tipo `ausentismo.resumen_diario.YYYY-MM-DD`.

## 1) Configuracion de entorno

Verificar en `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/Santiago

NOTIFICATIONS_V2_ENABLED=true
NOTIFICATIONS_V2_SCHEDULE_TIME=08:03
NOTIFICATIONS_V2_SCHEDULE_TIMEZONE=America/Santiago

QUEUE_CONNECTION=database
NOTIFICATIONS_V2_QUEUE_CONNECTION=database
NOTIFICATIONS_V2_QUEUE_NAME=default
```

Aplicar cache de config en produccion:

```bash
php artisan config:clear
php artisan config:cache
```

## 2) Scheduler (obligatorio)

Agregar en crontab del usuario del deploy:

```cron
* * * * * php /var/www/4toTOP/artisan schedule:run >> /dev/null 2>&1
```

Validar:

```bash
php artisan schedule:list
```

Debe aparecer `notificaciones:ausentismo-diario` con hora 08:03.

## 3) Worker de cola persistente

### Opcion A: Supervisor (Linux)

Archivo `/etc/supervisor/conf.d/4totop-queue.conf`:

```ini
[program:4totop-queue]
command=/usr/bin/php /var/www/4toTOP/artisan queue:work database --queue=default --tries=3 --timeout=120 --sleep=3
directory=/var/www/4toTOP
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/4toTOP/storage/logs/supervisor-queue.log
stopwaitsecs=3600
```

Activar:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start 4totop-queue
sudo supervisorctl status 4totop-queue
```

### Opcion B: NSSM (Windows)

Usar el plan ya documentado en `docs/documentacion/PlanNSSMQueueWorkerWindows.md`.

## 4) Prueba de punta a punta

Sin esperar las 08:03, ejecutar prueba manual:

```bash
php artisan notificaciones:ausentismo-diario --date=2026-05-06 --force
```

Verificar:

1. Se crea un registro en `notification_dispatches`.
2. Se crean destinatarios en `notification_recipients`.
3. Se crea intento en `notification_attempts`.
4. Estado final `sent` o `failed` con `failure_reason` claro.

Consultas utiles:

```sql
SELECT id, event_key, status, queued_at, sent_at, failed_at, failure_reason
FROM notification_dispatches
WHERE event_key LIKE 'ausentismo.resumen_diario.%'
ORDER BY id DESC
LIMIT 10;
```

```sql
SELECT id, queue, attempts, reserved_at, available_at, created_at
FROM jobs
ORDER BY id DESC
LIMIT 10;
```

```sql
SELECT id, queue, failed_at
FROM failed_jobs
ORDER BY id DESC
LIMIT 10;
```

## 5) Monitoreo minimo

- Alertar si no existe dispatch diario de ausentismo antes de 08:10.
- Alertar si crece `failed_jobs`.
- Revisar `storage/logs/laravel.log` buscando:
  - `notifications_v2.dispatch.created`
  - `notifications_v2.job.sent`
  - `notifications_v2.mail.recipient_failed`

## 6) Operacion en despliegues

En cada deploy:

```bash
php artisan migrate --force
php artisan config:cache
php artisan queue:restart
```

Comprobar que scheduler y worker siguen activos.

## 7) Falla comun y accion

- **No se encola a las 08:03:** scheduler no esta corriendo (cron/schedule:work caido).
- **Se encola pero no se envia:** worker caido o SMTP invalido.
- **Idempotencia evita reenvio:** usar `--force` para prueba manual controlada.
