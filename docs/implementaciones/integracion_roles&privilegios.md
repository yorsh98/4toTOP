# Integracion de Roles, Privilegios y SuperAdmin (Aterrizado a 4toTOP)

## 1) Objetivo de este plan

Implementar por etapas un subsistema RBAC (Role Based Access Control) en el proyecto actual:

- Roles (SuperAdmin, Admin, Operador, Consulta).
- Privilegios granulares por modulo real del sistema.
- SuperAdmin con recuperacion segura y sin riesgo de bloqueo total.

Este plan esta escrito para ejecutar por sprint, con entregables verificables en cada etapa.

---

## 2) Contexto tecnico real del proyecto

- Stack actual: Laravel 11 + PHP 8.2 + Livewire 3 + Volt.
- Guard de autenticacion: `web` (`config/auth.php`).
- Rutas principales en `routes/web.php` y auth en `routes/auth.php`.
- Hay modulos legacy con nombres no estandar (controladores en minuscula).
- Existen rutas sensibles con middleware parcial o sin proteccion uniforme.

Decisiones base:

1. Usar `spatie/laravel-permission`.
2. Convencion de permisos: `modulo.accion`.
3. Resolver autorizacion en backend (middleware/policies/gates), no solo ocultando botones.

---

## 3) Inventario de modulos reales (extraido de `routes/web.php`)

## 3.1 Modulos y endpoints actuales

### Publicos o mixtos (hoy)

- `/` (welcome + turno enviado)
- `/tabla2`
- `/tablas`
- `/guiastelefonicas`
- `/acc`, `/doc`, `/proc`
- `/migraunijud`
- `/buscadorsentencias`
- `/Monitor`
- `/ausencias`
- `/programacion/mail/{fecha}`
- `/preview/progra/{fecha?}`
- `/csrf-refresh`

### Protegidos por auth (hoy)

- `/turno` (GET)
- `/addfuncionario/*`
- `/guias`
- `/AdmAudv`
- `/dashboard`, `/profile`
- `/ausentismo`
- `POST /enviar-solicitud`

### Sensibles que requieren revision inmediata

- `PATCH /turno/{id}` tiene `withoutMiddleware(VerifyCsrfToken::class)`.
- Exports y envio de correo tienen impacto operativo y deben tener privilegio explicito.
- Monitor y varias vistas operativas hoy no exigen auth.

---

## 4) Catalogo inicial de roles

- `SuperAdmin`: control total, seguridad, auditoria, recuperacion.
- `AdminOperacion`: gestiona modulos operativos, no gestiona seguridad global.
- `Operador`: crea/edita datos operativos del dia.
- `Consulta`: solo lectura y reportes permitidos.

---

## 5) Catalogo inicial de privilegios (v1) aterrizado

## 5.1 Seguridad y administracion

- `seguridad.roles.ver`
- `seguridad.roles.gestionar`
- `seguridad.permisos.ver`
- `seguridad.permisos.gestionar`
- `seguridad.usuarios_roles.gestionar`
- `seguridad.auditoria.ver`

## 5.2 Turnos

- `turnos.ver`
- `turnos.editar`

## 5.3 Audiencias / Monitor / Export

- `audiencias.ver`
- `audiencias.crear`
- `audiencias.editar`
- `audiencias.estado.cambiar`
- `audiencias.exportar`
- `audiencias.monitor.ver`

## 5.4 Oficios / Libertades / Solicitudes

- `oficios.ver`
- `oficios.crear`
- `oficios.editar`
- `oficios.enviar_solicitud`
- `libertades.ver`
- `libertades.crear`
- `libertades.editar`

## 5.5 Otros modulos operativos

- `guias.ver`
- `guias.editar`
- `ausentismo.ver`
- `ausentismo.editar`
- `progra.ver`
- `progra.mail.enviar`
- `sentencias.ver`

---

## 6) Mapa ruta -> permiso (primer corte)

Esta matriz se implementa en Sprint 2 y se ajusta en Sprint 3.

- `GET /turno` -> `turnos.ver`
- `PATCH /turno/{id}` -> `turnos.editar`
- `GET /Monitor` -> `audiencias.monitor.ver`
- `GET /audiencias/export/diaria` -> `audiencias.exportar`
- `GET /AdmAudv` -> `audiencias.ver`
- `GET /ausentismo` -> `ausentismo.ver`
- `GET /ausencias` -> `ausentismo.ver`
- `GET /guias` -> `guias.ver`
- `POST /enviar-solicitud` -> `oficios.enviar_solicitud`
- `GET /programacion/mail/{fecha}` -> `progra.mail.enviar`
- `GET /buscadorsentencias` -> `sentencias.ver`

Nota: rutas de solo UI como `/doc`, `/acc`, `/proc`, `/tabla2`, `/tablas` se pueden agrupar bajo un permiso de lectura general si aplica.

---

## 7) Plan de implementacion por sprint

## Sprint 1 - Fundacion RBAC + SuperAdmin seguro

### Objetivo
Dejar la base tecnica instalada y un SuperAdmin operativo en todos los ambientes.

### Cambios de codigo

1. Instalar paquete:
   - `composer require spatie/laravel-permission`
2. Publicar migraciones/config:
   - `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
3. Ajustar modelo `User`:
   - agregar trait `HasRoles` en `app/Models/User.php`
4. Crear seeders:
   - `database/seeders/RolesAndPermissionsSeeder.php`
   - `database/seeders/SuperAdminSeeder.php`
5. Crear comando de recuperacion:
   - `app/Console/Commands/EnsureSuperAdmin.php`
6. Registrar comando en `routes/console.php` o kernel segun estructura Laravel 11.

### Datos iniciales obligatorios

- Rol `SuperAdmin` con todos los permisos v1.
- Usuario SuperAdmin inicial (correo definido por `.env`).
- Proceso idempotente para no duplicar roles/permisos.

### Verificacion

- `php artisan migrate`
- `php artisan db:seed --class=RolesAndPermissionsSeeder`
- `php artisan db:seed --class=SuperAdminSeeder`
- `php artisan ensure:superadmin --email=...`

### Criterio de cierre

- Existe al menos 1 usuario con rol `SuperAdmin` y puede autenticarse.

---

## Sprint 2 - Blindaje backend en rutas criticas

### Objetivo
Aplicar autorizacion real en rutas y controladores con mayor riesgo operativo.

### Prioridad alta (esta semana)

1. Turnos:
   - `GET /turno` -> permiso `turnos.ver`
   - `PATCH /turno/{id}` -> permiso `turnos.editar`
2. Audiencias:
   - `GET /AdmAudv` -> `audiencias.ver`
   - `GET /audiencias/export/diaria` -> `audiencias.exportar`
3. Monitor:
   - `GET /Monitor` -> `audiencias.monitor.ver`
4. Solicitud/oficios:
   - `POST /enviar-solicitud` -> `oficios.enviar_solicitud`
5. Correo programacion:
   - `GET /programacion/mail/{fecha}` -> `progra.mail.enviar`

### Archivos a intervenir

- `routes/web.php` (middleware `auth` + `can:*` por ruta)
- Controladores:
  - `app/Http/Controllers/TurnoController.php`
  - `app/Http/Controllers/AudienciasExportController.php`
  - `app/Http/Controllers/PrograMailController.php`
  - `app/Http/Controllers/SistOficioLibertadesController.php`
  - `app/Http/Controllers/MonitorController.php`

### Regla tecnica

- Validar permiso en middleware y en accion critica (defensa en profundidad).
- Mantener respuestas 403 consistentes cuando falte permiso.

### Verificacion

- Probar URL directa con usuario sin permiso -> 403.
- Probar usuario con permiso -> flujo normal.

### Criterio de cierre

- Ninguna de las rutas criticas puede ejecutarse sin permiso explicito.

---

## Sprint 3 - Livewire/Volt + politicas por dominio

### Objetivo
Completar autorizacion dentro de componentes y operaciones internas.

### Foco en componentes actuales

- `app/Livewire/AudienciaForm.php`
- `app/Livewire/CambiarEstadoAudiencia.php`
- `app/Livewire/MonitorAudiencias.php`
- `app/Livewire/OficioTabla.php`
- `app/Livewire/LibertadTabla.php`
- `app/Livewire/Ausentismo.php`
- `app/Livewire/AusentismoCalendario.php`
- `app/Livewire/BuscadorSentenciasTabla.php`

### Tareas

1. Reemplazar checks ad-hoc de auth por autorizacion por permiso donde corresponda.
2. Crear policies para modelos principales:
   - `Audiencia`, `Turno`, `Oficio`, `Libertad`, `Ausentismo`, `Guias`.
3. Usar `authorize()`/`can()` en acciones create/edit/delete/export/estado.
4. Alinear botones/acciones en Blade para no mostrar operaciones no permitidas.

### Archivos esperados

- `app/Policies/*Policy.php`
- `app/Providers/AuthServiceProvider.php` (si aplica registro explicito)
- vistas Blade/Livewire de cada modulo para ocultar acciones no permitidas.

### Criterio de cierre

- Un usuario sin permiso no puede ejecutar accion ni por UI ni por request manual.

---

## Sprint 4 - UI de administracion de roles y privilegios

### Objetivo
Permitir administracion operativa de seguridad desde la app.

### Tareas

1. Crear modulo `Seguridad` (solo `SuperAdmin` por defecto).
2. Pantallas:
   - listado/creacion/edicion de roles
   - asignacion de permisos a roles
   - asignacion de roles a usuarios
3. Reglas anti-bloqueo:
   - no eliminar ultimo `SuperAdmin`
   - no auto-revocarse `seguridad.*` sin confirmacion fuerte
4. Agregar confirmaciones y mensajes de impacto.

### Propuesta de rutas nuevas

- `/seguridad/roles`
- `/seguridad/permisos`
- `/seguridad/usuarios-roles`

### Criterio de cierre

- Seguridad gestionable sin tocar seeders/codigo para cambios operativos normales.

---

## Sprint 5 - Auditoria y trazabilidad

### Objetivo
Registrar quien cambio que en seguridad.

### Tareas

1. Crear tabla de auditoria (ej: `security_audits`).
2. Registrar eventos:
   - rol creado/editado/eliminado
   - permiso asignado/revocado
   - rol asignado/revocado a usuario
3. Guardar actor, timestamp, entidad, antes/despues, IP.
4. Vista de consulta de auditoria para SuperAdmin.

### Criterio de cierre

- Todo cambio de privilegios es trazable y consultable.

---

## Sprint 6 - QA de autorizacion + despliegue gradual

### Objetivo
Salir a produccion con riesgo controlado.

### Tareas de testing

1. Crear pruebas Feature nuevas:
   - `tests/Feature/Security/AuthorizationRoutesTest.php`
   - `tests/Feature/Security/AuthorizationLivewireTest.php`
   - `tests/Feature/Security/SuperAdminSafetyTest.php`
2. Casos minimos por modulo:
   - usuario sin permiso recibe 403
   - usuario con permiso obtiene 200/redirect esperado
3. Validar flujos auth existentes (no romper Breeze/Volt):
   - `tests/Feature/Auth/AuthenticationTest.php`

### Comandos recomendados

- `./vendor/bin/pint --test`
- `php artisan test tests/Feature/Security/AuthorizationRoutesTest.php`
- `php artisan test tests/Feature/Security/AuthorizationLivewireTest.php`
- `php artisan test tests/Feature/Security/SuperAdminSafetyTest.php`
- `php artisan test --filter="users can authenticate using the login screen"`

### Go-live gradual

1. Deploy 1: solo infraestructura + SuperAdmin.
2. Deploy 2: rutas criticas (turnos/audiencias/export/mail).
3. Deploy 3: Livewire + modulo seguridad + auditoria.

### Criterio de cierre

- Produccion estable, sin bypass de permisos, con trazabilidad activa.

---

## 8) Matriz inicial sugerida rol -> privilegios

## SuperAdmin

- Todos los permisos.

## AdminOperacion

- `turnos.*`, `audiencias.*`, `oficios.*`, `libertades.*`, `guias.*`, `ausentismo.*`, `progra.ver`
- sin `seguridad.permisos.gestionar` ni `seguridad.roles.gestionar`

## Operador

- `turnos.ver`, `turnos.editar`
- `audiencias.ver`, `audiencias.crear`, `audiencias.editar`, `audiencias.monitor.ver`
- `oficios.ver`, `oficios.crear`
- `ausentismo.ver`

## Consulta

- `turnos.ver`, `audiencias.ver`, `audiencias.monitor.ver`, `guias.ver`, `sentencias.ver`, `progra.ver`

---

## 9) Riesgos concretos del proyecto y mitigacion

- Riesgo: rutas legacy sin `auth` uniforme.
  - Mitigacion: Sprint 2 empieza por `routes/web.php` y aplica `auth` + `can` en endpoints sensibles.
- Riesgo: endpoint con CSRF deshabilitado (`PATCH /turno/{id}`).
  - Mitigacion: restringir por permiso estricto, revisar necesidad real de `withoutMiddleware` y compensar con validaciones extra.
- Riesgo: bloqueo administrativo.
  - Mitigacion: comando `ensure:superadmin` + regla de ultimo SuperAdmin no eliminable.
- Riesgo: regresion funcional por codigo legacy.
  - Mitigacion: rollout por modulo + tests de autorizacion por sprint.

---

## 10) Definicion de hecho global (DoD)

- Todas las rutas y acciones criticas tienen permiso explicito.
- No hay accion sensible ejecutable solo por estar autenticado.
- Existe SuperAdmin recuperable por comando.
- Hay UI de seguridad para gestion operativa.
- Hay auditoria de cambios de roles/permisos.
- Tests de autorizacion pasan en local/CI.

---

## 11) Orden sugerido de ejecucion real

1. Sprint 1 (base) en rama propia.
2. Sprint 2 sobre rutas criticas de negocio (turnos, audiencias, export, correo).
3. Sprint 3 para cerrar Livewire/policies.
4. Sprint 4 + 5 para operacion y control.
5. Sprint 6 para pruebas finales y despliegue gradual.

Este orden minimiza riesgo operativo y entrega seguridad util desde la semana 1.
