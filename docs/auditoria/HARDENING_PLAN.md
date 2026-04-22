# Plan de Hardening

Proyecto: `4toTOP`  
Fecha: `2026-04-22`

## Quick wins (0-7 dias)

- Deshabilitar scheduler obsoleto de POP3 (`correo:leer-ultimos-schedule`).
- Eliminar bypass de superadmin por correo fijo en `AppServiceProvider`.
- Retirar/bloquear `info.php`.
- Quitar hardcode de IMAP en `config/imap.php`.
- Cambiar mensajes de error visibles al usuario por mensajes genericos.
- Iniciar rotacion inmediata de credenciales expuestas (DB/SMTP/IMAP).

## Cambios de configuracion

### Secretos

- Migrar secretos a vault/KMS y dejar `.env` solo para desarrollo.
- Revisar uso de `bootstrap/cache/config.php` y proteger su lectura.
- Endurecer permisos de ficheros sensibles (`.env`, caches).

### Sesion y cookies

- Forzar en produccion:
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_HTTP_ONLY=true` (ya true por defecto)
  - `SESSION_SAME_SITE=lax` o `strict` segun necesidad
- Evaluar `SESSION_ENCRYPT=true` en produccion.

### Correo/IMAP

- Forzar TLS/SSL con validacion de certificado.
- Eliminar `notls` y `validate_cert=false` en configuraciones efectivas.

## Mejoras de codigo

1. Convertir endpoint de envio `GET /programacion/mail/{fecha}` a `POST` con CSRF.
2. Endurecer autorizacion de rutas publicas sensibles (`/Monitor`, `/buscadorsentencias`, `/bitacora-pruebas`, previews operativas).
3. Mover documentos desde `public/documentos` a disco privado y servir con politica de acceso.
4. Ajustar `SuperAdminSeeder` para fallar si no hay secreto fuerte (sin fallback debil).
5. Eliminar retorno de excepciones crudas al usuario (`getMessage()`).
6. Consolidar validaciones y controles de autorizacion por recurso para exportaciones/envios.

## Mejoras de infraestructura

- Definir reverse proxy hardening baseline (HSTS, TLS minima, cipher policy, deny list).
- Definir segmentacion de red entre app, DB, workers y servicios de correo.
- Asegurar backups cifrados y pruebas periodicas de restauracion.
- Formalizar segregacion dev/test/prod con controles de promocion.

## Mejoras de monitoreo

- Centralizar logs (app, auth, jobs, errores) con retencion y alertas.
- Alertar eventos de seguridad:
  - cambios de roles/permisos,
  - intentos de acceso denegado repetidos,
  - fallos de envio anomalo,
  - errores 5xx recurrentes.
- Definir umbrales y playbooks de respuesta para incidentes.

## Mejoras de IAM

- Eliminar privilegios implicitos por correo fijo.
- Asegurar principio de minimo privilegio por rol.
- Implementar revisiones periodicas de permisos/roles.
- Evaluar MFA para cuentas privilegiadas.
- Formalizar procedimiento de alta/baja/cambio de privilegios.

## Mejoras de continuidad y respaldo

- Establecer politica RPO/RTO por servicio.
- Programar pruebas de restauracion trimestral con evidencia.
- Documentar runbook de continuidad para servicios criticos (correo, BD, colas).
- Definir fallback operativo para notificaciones en caso de caida de SMTP principal.

## Backlog priorizado

### P0 (inmediato)

1. Rotacion de secretos expuestos y revocacion de credenciales comprometidas.
2. Eliminar hardcode IMAP + cifrado inseguro.
3. Eliminar bypass superadmin por email fijo.
4. Quitar password por defecto en `SuperAdminSeeder`.

### P1 (30 dias)

1. Migrar envio por GET a POST + CSRF + rate limit.
2. Cerrar rutas publicas sensibles con `auth + can`.
3. Actualizar dependencias npm vulnerables.
4. Desactivar definitivamente comando obsoleto de POP3 en scheduler.

### P2 (90 dias)

1. Migrar documentos a almacenamiento privado con control por recurso.
2. Implementar secret manager corporativo.
3. Formalizar politicas de seguridad/continuidad/incidentes y evidencia de cumplimiento.
4. Integrar monitoreo de seguridad y pruebas de restore recurrentes.
