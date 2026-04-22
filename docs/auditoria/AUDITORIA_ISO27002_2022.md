# Auditoria Tecnica de Seguridad y Gap Analysis

Proyecto: `4toTOP`  
Fecha: `2026-04-22`  
Marco de referencia: `ISO/IEC 27002:2022` (alineado a enfoque de control de `ISO/IEC 27001:2022`)

## Resumen ejecutivo

Se realizo una auditoria tecnica integral del repositorio y su configuracion operativa. Se detectaron controles positivos (RBAC con Spatie, registro de auditoria de cambios de seguridad, rate limit de login, middleware de cabeceras), pero tambien brechas criticas y altas que impiden considerar el sistema como alineado de forma robusta con ISO/IEC 27002:2022.

Riesgos dominantes:

- Gestion insegura de secretos (hardcode + credenciales expuestas en runtime local).
- Exposicion de rutas y datos operativos sin autenticacion uniforme.
- Controles de acceso privilegiado con bypass por correo fijo.
- Configuraciones inseguras de correo/IMAP (sin cifrado estricto).
- Superficie de ataque ampliada por endpoints con efectos por metodo GET.

Estado global estimado:

- Madurez de seguridad observada: **Bajo**.
- Alineamiento estimado ISO/IEC 27002:2022: **Parcial**.

## Arquitectura detectada

### Stack real

- Backend: `Laravel 13.4.0`, `PHP 8.4`, `MariaDB`.
- Frontend: `Vite 6`, `Tailwind 3`, `Bootstrap 5`, `Livewire 3.7`, `Volt 1.10`.
- IAM: `spatie/laravel-permission` + `Gate::authorize`.
- Jobs/colas: conexion `database` (`jobs`, `failed_jobs`) + scheduler en `routes/console.php`.
- Notificaciones: modulo `NotificationsV2` con orquestador, recipients y retries.

### Componentes y flujos principales

- Web monolito con rutas en `routes/web.php`.
- Subida de archivos en backoffice (`DocumentosBackofficePage`) y almacenamiento en `public`.
- Exportacion de audiencias a Excel (`AudienciasExportController`).
- Envio de correos operativos y de notificaciones (controladores + jobs V2).
- Auditoria de acciones de seguridad en `security_audits`.

### Activos criticos

- Credenciales SMTP/IMAP/DB y `APP_KEY`.
- Datos de audiencias, RIT/RUC, ausentismos, funcionarios y bitacoras.
- Roles/permisos y cuentas administrativas.
- Documentos operativos cargados/publicados.

### Entornos detectados

- Desarrollo/local: `.env` (`APP_ENV=local`, `APP_DEBUG=true`).
- Testing: `.env.testing`, `phpunit.xml` con DB MySQL dedicada.
- Produccion: no hay configuracion IaC o pipeline de despliegue en repo; evidencia parcial via docs operativas.

### Vacios de evidencia

- Controles fisicos de datacenter/oficinas/edge: **No verificable**.
- Hardening del host/OS/reverse proxy/WAF: **No verificable**.
- Evidencia real de backups automatizados y pruebas de restauracion: **No verificable** en codigo.

## Alcance y limites

Incluido:

- Codigo fuente backend/frontend, rutas, controladores, Livewire, migraciones, seeders, configs.
- Archivos de entorno de ejemplo y configuraciones de seguridad relevantes.
- Dependencias Composer/NPM y estado de auditoria de vulnerabilidades.
- Scheduler, jobs, logging, exportaciones, subida de archivos, notificaciones.
- Documentacion tecnica existente en `docs/`.

No incluido por falta de evidencia tecnica en repo:

- Infraestructura externa (cloud console, firewalls, IAM corporativo, SIEM real, backups reales).

## Metodologia

1. Descubrimiento de stack y arquitectura desde manifiestos (`composer.json`, `package.json`, configuraciones Laravel).
2. Inventario de superficies de ataque desde rutas, componentes y comandos.
3. Revision estatica de codigo para patrones de riesgo (secrets, autorizacion, validacion, logs, errores, uploads).
4. Validaciones no destructivas:
   - `composer audit --no-interaction`.
   - `npm audit --json`.
   - inspeccion de permisos de archivos sensibles.
5. Mapeo de hallazgos a controles ISO/IEC 27002:2022 (control probable cuando aplica).

## Hallazgos

> Formato: severidad, estado, evidencia y mapeo ISO probable.  
> El detalle completo y tabulado esta en `docs/auditoria/MATRIZ_GAP_ISO27002_2022.csv`.

[SEC-001]
Titulo: Credenciales hardcodeadas y IMAP inseguro  
Severidad: Critical  
Estado: ausente  
Activo afectado: Secretos de correo/IMAP  
Ubicacion/Evidencia: `config/imap.php:51`, `config/imap.php:55`, `config/imap.php:56`, `config/imap.php:53`, `config/imap.php:54`  
Descripcion tecnica: Se definen host/usuario/password directamente en codigo, con `notls` y `validate_cert=false`.  
Impacto: Exposicion de credenciales, MITM y compromisos de cuentas.  
Escenario de riesgo: atacante con acceso al repo o red interna puede interceptar/acceder a buzones.  
Mapeo ISO/IEC 27002:2022: 5.17, 8.24, 8.9, 8.21 (probable)  
Recomendacion: Secret manager + TLS obligatorio + validacion de certificado.  
Remediacion tecnica: Ver `PARCHES_PROPUESTOS.md` (Parche 1).  
Prioridad: P0  
Esfuerzo: medio

[SEC-002]
Titulo: Secretos expuestos en entorno local/runtime  
Severidad: Critical  
Estado: ausente  
Activo afectado: `APP_KEY`, DB, SMTP, IMAP  
Ubicacion/Evidencia: `.env:3`, `.env:30`, `.env:56`, `.env:71`, `.env:79`, `.env:87`, `.env:95`, `.env:117`, `bootstrap/cache/config.php`  
Descripcion tecnica: Secretos en texto plano y materializados en cache de configuracion.  
Impacto: Compromiso transversal de confidencialidad/integridad.  
Mapeo ISO: 5.17, 8.9, 8.24  
Recomendacion: Rotacion inmediata + permisos estrictos + vault.  
Prioridad: P0  
Esfuerzo: medio-alto

[SEC-003]
Titulo: Bypass de autorizacion por correo fijo (superadmin implicito)  
Severidad: High  
Estado: ausente  
Activo afectado: Control de acceso privilegiado  
Ubicacion/Evidencia: `app/Providers/AppServiceProvider.php:20`, `app/Providers/AppServiceProvider.php:38`  
Descripcion tecnica: `Gate::before` concede todo a un email fijo.  
Impacto: Si esa cuenta cae, el sistema completo queda comprometido.  
Mapeo ISO: 5.15, 5.16, 5.18, 8.2  
Recomendacion: Eliminar bypass y usar RBAC puro + break-glass formal.  
Prioridad: P0  
Esfuerzo: bajo

[SEC-004]
Titulo: Password por defecto debil en seeder de SuperAdmin  
Severidad: High  
Estado: ausente  
Activo afectado: Cuenta privilegiada  
Ubicacion/Evidencia: `database/seeders/SuperAdminSeeder.php:18`  
Descripcion tecnica: fallback `SUPERADMIN_PASSWORD=***REMOVED***`.  
Impacto: riesgo de takeover en bootstrap/deploy mal controlado.  
Mapeo ISO: 5.17, 8.5  
Recomendacion: fail-fast sin secreto fuerte + rotacion + doble control.  
Prioridad: P0  
Esfuerzo: bajo

[SEC-005]
Titulo: Rutas publicas con datos operativos sensibles  
Severidad: High  
Estado: parcialmente implementado  
Activo afectado: Confidencialidad de informacion judicial/operativa  
Ubicacion/Evidencia: `routes/web.php:112`, `routes/web.php:139`, `routes/web.php:140`, `routes/web.php:185`  
Descripcion tecnica: endpoints sin `auth/can` uniforme exponen datos de operacion.  
Impacto: fuga de informacion y riesgo reputacional/legal.  
Mapeo ISO: 5.15, 8.3, 5.34  
Recomendacion: clasificar datos y exigir autenticacion/autorizacion por recurso.  
Prioridad: P1  
Esfuerzo: medio

[SEC-006]
Titulo: Accion de envio ejecutable por GET  
Severidad: High  
Estado: ausente  
Activo afectado: Integridad operativa de envios  
Ubicacion/Evidencia: `routes/web.php:179`, `app/Http/Controllers/PrograMailController.php:221`  
Descripcion tecnica: envio de correo via GET; mala practica para accion con efecto.  
Impacto: riesgo CSRF/logico, disparos involuntarios, abuso operacional.  
Mapeo ISO: 8.28, 8.25, 8.3  
Recomendacion: migrar a POST + CSRF + rate limit + confirmacion explicita.  
Prioridad: P1  
Esfuerzo: bajo-medio

[SEC-007]
Titulo: Exposicion de errores internos al usuario final  
Severidad: Medium  
Estado: ausente  
Activo afectado: confidencialidad de informacion tecnica  
Ubicacion/Evidencia: `app/Http/Controllers/PrograMailController.php:281`, `app/Http/Controllers/PrograMailController.php:354`  
Descripcion tecnica: se concatena `getMessage()` en respuesta de UI.  
Impacto: filtrado de detalles internos explotables.  
Mapeo ISO: 8.28, 8.15  
Recomendacion: mensajes genericos al usuario y detalle solo en logs.  
Prioridad: P2  
Esfuerzo: bajo

[SEC-008]
Titulo: Scheduler invoca comando obsoleto inseguro cada minuto  
Severidad: High  
Estado: ausente  
Activo afectado: disponibilidad/seguridad operativa de correo  
Ubicacion/Evidencia: `routes/console.php:12`, `app/Console/Commands/LeerCorreosPOP3.php:2`, `app/Console/Commands/LeerCorreosPOP3.php:18`, `app/Console/Commands/LeerCorreosPOP3.php:25`  
Descripcion tecnica: comando marcado como obsoleto y con POP3 no cifrado sigue scheduleado.  
Impacto: deuda operativa + riesgo de abuso y errores recurrentes.  
Mapeo ISO: 5.37, 8.9, 8.21  
Recomendacion: desactivar scheduler y retirar/aislar codigo obsoleto.  
Prioridad: P1  
Esfuerzo: bajo

[SEC-009]
Titulo: Dependencias frontend con vulnerabilidades conocidas  
Severidad: High  
Estado: parcialmente implementado  
Activo afectado: supply chain y entorno de build/dev  
Ubicacion/Evidencia: `npm audit --json` (vite/picomatch/axios/follow-redirects)  
Descripcion tecnica: advisories abiertos, incluyendo alta en `vite` y `picomatch`.  
Impacto: riesgo en toolchain/dev server/pipeline.  
Mapeo ISO: 8.8, 5.21, 8.25  
Recomendacion: actualizar versiones y establecer politica SCA continua.  
Prioridad: P1  
Esfuerzo: bajo-medio

[SEC-010]
Titulo: Documentos en webroot sin control por recurso  
Severidad: Medium  
Estado: parcialmente implementado  
Activo afectado: documentos operativos  
Ubicacion/Evidencia: `public/documentos/*`, `app/Livewire/DocumentosBackofficePage.php:263`  
Descripcion tecnica: documentos accesibles por URL directa.  
Impacto: fuga de informacion y acceso no autorizado.  
Mapeo ISO: 8.3, 5.34, 5.12  
Recomendacion: mover a storage privado con descargas autorizadas y URLs temporales.  
Prioridad: P2  
Esfuerzo: medio

[SEC-011]
Titulo: Falta de endurecimiento estricto de sesion/cookies  
Severidad: Medium  
Estado: parcialmente implementado  
Activo afectado: sesiones autenticadas  
Ubicacion/Evidencia: `config/session.php:172`, `.env.example:34`  
Descripcion tecnica: `SESSION_SECURE_COOKIE` no forzado; cifrado de sesion desactivado por defecto.  
Impacto: mayor exposicion en despliegues mal configurados.  
Mapeo ISO: 8.5, 8.3, 8.24  
Recomendacion: baseline de prod seguro y validado por health checks.  
Prioridad: P2  
Esfuerzo: bajo

[SEC-012]
Titulo: Datos personales reales versionados en seeders  
Severidad: Medium  
Estado: ausente  
Activo afectado: privacidad/PII  
Ubicacion/Evidencia: `database/seeders/MailRecipientSeeder.php`  
Descripcion tecnica: lista extensa de correos personales/institucionales en codigo fuente.  
Impacto: riesgo de privacidad y cumplimiento.  
Mapeo ISO: 5.34, 5.12, 5.33  
Recomendacion: reemplazar por datos sinteticos y cargar reales fuera de git.  
Prioridad: P2  
Esfuerzo: medio

[SEC-013]
Titulo: Archivo `info.php` con `phpinfo()` en raiz del proyecto  
Severidad: Medium  
Estado: ausente  
Activo afectado: exposicion de metadatos de plataforma  
Ubicacion/Evidencia: `info.php:2`  
Descripcion tecnica: archivo util para fingerprinting si se publica por error.  
Impacto: facilita enumeracion para ataque dirigido.  
Mapeo ISO: 8.9, 8.28  
Recomendacion: eliminar archivo o bloquear explicitamente en servidor web.  
Prioridad: P2  
Esfuerzo: bajo

[SEC-014]
Titulo: Gobernanza de seguridad no evidenciada formalmente en repo  
Severidad: Medium  
Estado: no verificable  
Activo afectado: cumplimiento y operacion  
Ubicacion/Evidencia: ausencia de politicas formales de seguridad/incidentes/retencion en repo  
Descripcion tecnica: no hay evidencia formal de politicas organizacionales y procedimientos obligatorios.  
Impacto: debilidad de cumplimiento y repetibilidad operativa.  
Mapeo ISO: 5.1, 5.24, 5.26, 5.29, 5.30, 5.31, 5.37 (probable)  
Recomendacion: formalizar paquete documental de ISMS operativo.  
Prioridad: P2  
Esfuerzo: medio

## Fortalezas

- RBAC implementado y extendido (`spatie/laravel-permission`, `Gate::authorize(...)`).
- Auditoria de cambios de seguridad persistida (`security_audits`).
- Rate limiting de autenticacion (`LoginForm`).
- Validaciones de input presentes en gran parte de flujos Livewire/controladores.
- Middleware de cabeceras de seguridad activo (`SecurityHeaders`).

## Debilidades

- Gestion de secretos y cifrado de transporte de correo deficiente.
- Exposicion de datos por rutas publicas y documentos en webroot.
- Operacion con comando obsoleto inseguro en scheduler.
- Riesgos de supply chain no mitigados completamente.
- Falta de evidencia de controles organizacionales formales y continuidad probada.

## Riesgos criticos (Top 10)

1. Secrets hardcodeados + IMAP no seguro.
2. Secretos expuestos en `.env` y cache runtime.
3. Bypass de privilegios por correo fijo.
4. Password default debil en seeder de SuperAdmin.
5. Rutas operativas publicas sin auth uniforme.
6. Envio de correo ejecutable por GET.
7. Scheduler obsoleto inseguro cada minuto.
8. Vulnerabilidades npm (vite/picomatch/axios).
9. Errores internos expuestos a usuario final.
10. Documentos operativos en ruta publica.

## Conclusiones

El sistema muestra mejoras tecnicas reales, pero mantiene brechas estructurales en secretos, control de acceso y exposicion de informacion. Sin una remediacion priorizada en 90 dias, el riesgo operacional y de cumplimiento permanece alto.

Para una auditoria formal tipo certificacion, se requiere evidencia adicional de gobierno, controles operativos en produccion, y prueba de efectividad de continuidad/recuperacion.

## Roadmap de remediacion

### Fase 7 dias (contencion)

- Rotar credenciales expuestas (DB/SMTP/IMAP) y revocar las comprometidas.
- Eliminar hardcode de secretos en `config/imap.php`.
- Desactivar scheduler obsoleto (`correo:leer-ultimos-schedule`).
- Eliminar bypass de superadmin por correo fijo.
- Bloquear/retirar `info.php`.

### Fase 30 dias (endurecimiento)

- Convertir envios por GET a POST con CSRF y rate limiting.
- Cerrar rutas publicas sensibles con `auth + can`.
- Mover documentos a storage privado con acceso autorizado.
- Actualizar dependencias npm vulnerables y fijar proceso SCA.
- Normalizar politicas de logs sin error interno expuesto a usuario.

### Fase 90 dias (madurez)

- Implementar gestion de secretos centralizada (vault/KMS).
- Formalizar politicas y procedimientos (incidentes, continuidad, retencion, privacidad).
- Ejecutar y evidenciar pruebas de backup/restore.
- Establecer segregacion formal dev/test/prod y controles de cambio.
- Integrar monitoreo de seguridad y alertamiento operacional.
