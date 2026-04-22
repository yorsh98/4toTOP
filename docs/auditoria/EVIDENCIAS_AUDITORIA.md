# Evidencias de Auditoria

Fecha de levantamiento: `2026-04-22`

Este indice lista evidencias tecnicas usadas para sustentar hallazgos del gap analysis.

## 1) Stack y configuracion base

- `composer.json`  
  Relevancia: dependencias backend, framework, librerias criticas de seguridad y correo.

- `package.json`  
  Relevancia: dependencias frontend y superficie supply chain.

- `phpunit.xml`  
  Relevancia: entorno de pruebas y DB de test.

- `bootstrap/app.php`  
  Relevancia: middleware global (`SecurityHeaders`) y enrutamiento.

## 2) Secretos, credenciales y cifrado

- `.env`  
  Relevancia: evidencia de gestion de secretos local (credenciales sensibles en claro).

- `config/imap.php`  
  Relevancia: hardcoded credentials, `notls`, `validate_cert=false`.

- `config/mail.php`  
  Relevancia: multiples mailers con credenciales por entorno.

- `config/database.php`  
  Relevancia: configuracion de DB y uso de password desde entorno.

- `bootstrap/cache/config.php`  
  Relevancia: materializacion de configuracion sensible en cache runtime.

## 3) Autenticacion, autorizacion e IAM

- `app/Providers/AppServiceProvider.php`  
  Relevancia: bypass de privilegios por correo fijo en `Gate::before`.

- `database/seeders/SuperAdminSeeder.php`  
  Relevancia: fallback de password debil para cuenta privilegiada.

- `app/Livewire/Forms/LoginForm.php`  
  Relevancia: control de rate limiting y flujo de login.

- `config/permission.php`  
  Relevancia: baseline de RBAC con Spatie.

- `database/seeders/RolesAndPermissionsSeeder.php`  
  Relevancia: catalogo de permisos y roles iniciales.

- `app/Livewire/Security/*`  
  Relevancia: gestion de roles/permisos y trazabilidad en seguridad.

## 4) Rutas, exposicion y superficie de ataque

- `routes/web.php`  
  Relevancia: rutas publicas/sensibles y controles `auth/can`.

- `routes/auth.php`  
  Relevancia: autenticacion y throttling de verificacion.

- `routes/console.php`  
  Relevancia: scheduler y comandos operativos (incluye flujo obsoleto).

## 5) Logs, trazabilidad y monitoreo

- `config/logging.php`  
  Relevancia: canales de log, retencion y niveles.

- `database/migrations/2026_03_31_130000_create_security_audits_table.php`  
  Relevancia: base de auditoria de acciones administrativas.

- `app/Livewire/Security/Concerns/LogsSecurityAudit.php`  
  Relevancia: registro de actor, IP, user-agent, before/after.

## 6) Carga/descarga de archivos y datos

- `app/Livewire/DocumentosBackofficePage.php`  
  Relevancia: upload, validaciones de archivos y almacenamiento en disco publico.

- `config/filesystems.php`  
  Relevancia: discos `local/public` y visibilidad.

- `public/documentos/*`  
  Relevancia: documentos operativos accesibles por URL directa.

- `storage/app/backoffice/documentos_catalogo.json`  
  Relevancia: catalogo de documentos persistido en storage.

## 7) Correo y notificaciones

- `app/Http/Controllers/PrograMailController.php`  
  Relevancia: envio de correos, uso de GET con efecto, manejo de errores.

- `app/Http/Controllers/NotificationsV2PreviewController.php`  
  Relevancia: endpoints de preview y control de acceso.

- `app/NotificationsV2/Application/Jobs/ProcessNotificationDispatchJob.php`  
  Relevancia: reintentos, estados y trazabilidad de envio.

- `app/NotificationsV2/Application/Services/DatabaseNotificationOrchestrator.php`  
  Relevancia: idempotencia, recipients, cola y feature flag.

## 8) Hallazgos de supply chain

- Salida `composer audit --no-interaction`  
  Relevancia: sin advisories detectados en Composer al momento de auditoria.

- Salida `npm audit --json`  
  Relevancia: vulnerabilidades detectadas en `vite`, `picomatch`, `axios`, `follow-redirects`.

## 9) Evidencia documental operativa

- `docs/implementaciones/sprint0_runbook_funcionarios.md`  
  Relevancia: procedimiento operativo de backup/rollback documentado.

- `docs/implementaciones/sprint7_qa_despliegue.md`  
  Relevancia: checklist de despliegue y QA tecnico.

- `docs/implementaciones/integracion_roles&privilegios.md`  
  Relevancia: plan de RBAC y seguridad por sprints.

## 10) Evidencia de riesgos adicionales

- `info.php`  
  Relevancia: presencia de `phpinfo()` potencialmente expuesto.

- `app/Console/Commands/LeerCorreosPOP3.php`  
  Relevancia: comando obsoleto con POP3 no cifrado y observaciones operativas.

## Notas de tratamiento de evidencia

- Se enmascararon secretos y no se incluyen credenciales en claro en este informe.
- La auditoria fue no destructiva y orientada a evidencia de repositorio/configuracion.
- Controles fisicos y parte de infraestructura productiva quedan marcados como **No verificable con la evidencia disponible**.
