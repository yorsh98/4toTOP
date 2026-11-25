# Implementacion Envio Bitacora

## Objetivo

Integrar la vista `bitacora-pruebas` con el sistema de notificaciones para enviar por correo:

- Enlace de documentos del juicio.
- Ruta publica del portal de Bitacora de Pruebas.

La funcionalidad incluye 3 campos de correo destinatario y un boton de envio.

## Alcance implementado

### 1) Formulario en Bitacora Pruebas (Backoffice)

Se agrego un bloque nuevo en `resources/views/livewire/bitacora-pruebas-page.blade.php` con:

- `Correo destinatario 1`
- `Correo destinatario 2`
- `Correo destinatario 3`
- `Enlace documentos`
- `Ruta portal bitacora publica`
- Boton `Enviar notificacion`

El bloque reutiliza estilos existentes del modulo.

### 2) Logica Livewire para envio

Se extendio `app/Livewire/BitacoraPruebasPage.php` con:

- Nuevas propiedades publicas para los 3 correos y los 2 enlaces.
- Inicializacion de la ruta publica en `mount()` con `route('bitacora-pruebas.public')`.
- Metodo `sendBitacoraNotification()` con:
  - autorizacion `bitacora_pruebas.editar`,
  - validacion de emails y URLs,
  - normalizacion/deduplicacion de correos,
  - validacion de al menos 1 destinatario,
  - delegacion de envio al servicio `BitacoraPruebasNotifier`.

### 3) Servicio de notificacion (V2 + fallback legacy)

Se creo `app/NotificationsV2/Application/Services/BitacoraPruebasNotifier.php`.

Comportamiento:

- Si `notifications_v2.enabled=false`:
  - envia por `Notification::route('mail', ...)` usando `BitacoraPruebasNotification` (legacy).
- Si `notifications_v2.enabled=true`:
  - usa `NotificationOrchestrator` (`dispatch`) y registra plantilla/tipo automaticamente.

### 4) Notificacion legacy de correo

Se creo `app/Notifications/BitacoraPruebasNotification.php` para renderizar correo cuando V2 esta deshabilitado.

Incluye:

- Asunto: `Notificacion de Bitacora de Pruebas`.
- Nombre de remitente dinamico.
- Dos botones:
  - `Ir a enlace documentos`
  - `Ir a bitacora de pruebas`

### 5) Plantilla HTML de correo

Se creo `resources/views/emails/v2/bitacora-pruebas-outlook.blade.php` siguiendo el estilo institucional de notificaciones:

- Header institucional `4to Tribunal de Juicio Oral en lo Penal de Santiago`.
- Mensaje: `{{ senderName }} le ha enviado esta notificacion.`
- Dos CTA (botones) con enlaces solicitados.
- Footer: `Correo automatico del Sistema de Notificaciones.`

### 6) Template por defecto para Orquestador V2

La plantilla oficial se define en `app/NotificationsV2/Support/OutlookEmailTemplates.php` con:

- `bitacoraPruebasHtml()`
- `bitacoraPruebasText()`

Estas funciones son usadas para crear/actualizar `NotificationTemplate` del tipo `bitacora_pruebas.notificacion`.

## Pruebas automatizadas

Se agrego `tests/Feature/NotificationsV2/BitacoraPruebasNotifierTest.php` con dos escenarios:

1. `notifications_v2.enabled=false`:
   - verifica envio on-demand de notificacion legacy.
2. `notifications_v2.enabled=true`:
   - verifica creacion de `NotificationDispatch` y `NotificationRecipient` via orquestador.

## Archivos modificados

- `app/Livewire/BitacoraPruebasPage.php`
- `resources/views/livewire/bitacora-pruebas-page.blade.php`
- `app/NotificationsV2/Support/OutlookEmailTemplates.php`
- `routes/web.php`

## Archivos nuevos

- `app/Notifications/BitacoraPruebasNotification.php`
- `app/NotificationsV2/Application/Services/BitacoraPruebasNotifier.php`
- `resources/views/emails/v2/bitacora-pruebas-outlook.blade.php`
- `tests/Feature/NotificationsV2/BitacoraPruebasNotifierTest.php`
- `docs/implementaciones/ImplementacionEnvioBitacora.md`

## Consideraciones operativas

- Para usar orquestador V2 en produccion:
  - `NOTIFICATIONS_V2_ENABLED=true`
  - worker de cola activo (`queue:work` / supervisor)
  - mailer y credenciales correctas.

- Si V2 se desactiva, el flujo sigue operativo por fallback legacy.
