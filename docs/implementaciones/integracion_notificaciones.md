# Integracion del Sistema de Notificaciones por Correo (V2)

## Objetivo

Implementar un sistema centralizado de notificaciones por correo en paralelo a los envios actuales, usando arquitectura escalable y sin tocar flujos legacy durante el MVP.

## Alcance MVP (fase paralela)

- Construir modulo nuevo de notificaciones (V2) sin migrar correos existentes.
- Activar solo 2 subsistemas/eventos iniciales:
  1. Turnos: envio al asignar/actualizar turno.
  2. Ausentismo: resumen diario matinal de funcionarios no disponibles.
- Usar `App\Services\FuncionarioCatalog` como fuente de destinatarios/correos.
- Soportar envio asincrono con colas, trazabilidad y reintentos.

## Fuera de alcance (por ahora)

- Migracion de `ProgramacionDiariaHtmlMail` y `UserAccountStatusNotification`.
- Reemplazo de llamadas legacy a `Mail::` o `notify()` ya existentes.
- Refactor global de todos los controladores/componentes que hoy envian correo.

## Principios de diseno

- SOLID: responsabilidades separadas por capa.
- Event-driven: el dominio dispara eventos y el modulo V2 orquesta envio.
- Idempotencia: evitar duplicados por evento.
- Observabilidad: cada envio debe quedar registrado y trazable.
- Configurable: plantillas y destinatarios gestionables sin hardcode innecesario.

## Definicion funcional cerrada del MVP

### Evento 1: `turno.asignado`

- Disparador: guardado de asignacion en Turno 1 o Turno 2 ACD.
- Contenido minimo:
  - fecha de turno,
  - bloque (Turno 1 / Turno 2 ACD),
  - slot actualizado (ej. TM1, TF2, ACDJ1),
  - nombre asignado.
- Destinatarios:
  - primario: funcionario asignado (si existe correo en catalogo),
  - opcional: copia a roles de jefatura (configurable).
- Regla de excepcion:
  - si el nombre no tiene correo asociado en catalogo, no bloquear guardado; registrar warning en trazabilidad.

### Evento 2: `ausentismo.resumen_diario`

- Disparador: tarea programada diaria en la manana.
- Hora de ejecucion: 08:05 AM (`America/Santiago`).
- Contenido minimo:
  - fecha de reporte,
  - listado de funcionarios no disponibles vigentes para el dia,
  - cargo, tipo de permiso, rango de fechas, observacion.
- Destinatarios:
  - todas las personas registradas en `FuncionarioCatalog` con correo valido.
- Regla cuando no hay ausentes:
  - enviar correo indicando "no hay ausencias registradas".

## Definiciones operativas confirmadas

- Resumen diario de ausentismo: 08:05 AM, zona `America/Santiago`.
- Destinatarios del resumen diario: todos los funcionarios con correo en `FuncionarioCatalog`.
- Si no hay ausentes: se envia correo de control con mensaje "no hay ausencias registradas".
- Alcance de turnos para MVP: solo Turno 1 y Turno 2 ACD (Turno 3 queda fuera de este plan).

## Plan por sprints

## Sprint 1 - Definicion funcional y reglas operativas

**Estado:** completado

### Objetivo

Cerrar reglas funcionales exactas para los 2 eventos del MVP.

### Tareas

- Confirmar matriz final de disparadores para `turno.asignado`.
- Confirmar destinatarios de turno (asignado y/o jefatura en copia).
- Registrar hora de envio del resumen diario de ausentismo: 08:05 (`America/Santiago`).
- Registrar destinatarios del resumen diario: todos los funcionarios con correo en `FuncionarioCatalog`.
- Registrar politica "sin ausentes": enviar correo indicando que no hay ausencias registradas.

### Entregables

- Matriz final `evento -> disparador -> destinatarios -> plantilla -> SLA`.
- Reglas funcionales aprobadas para MVP paralelo.

### Criterio de salida

Eventos, destinatarios y comportamiento operativo aprobados sin ambiguedad.

### Matriz funcional cerrada (MVP)

| Evento | Disparador | Destinatarios | Canal | Prioridad | SLA |
|---|---|---|---|---|---|
| `turno.asignado` | Guardado de cambios en Turno 1 o Turno 2 ACD (`saveTurno1`, `saveTurno2Acd`) | Primario: funcionario asignado con correo valido en catalogo. Copia jefatura: opcional, desactivada por defecto en MVP. | Email | Normal | Encolado inmediato tras guardado exitoso |
| `ausentismo.resumen_diario` | Scheduler diario a las 08:05 (`America/Santiago`) | Todas las personas registradas en `FuncionarioCatalog` con correo valido | Email | Alta operativa | Envio en ventana de la corrida diaria |

### Reglas funcionales aprobadas

- `turno.asignado` se aplica solo a Turno 1 y Turno 2 ACD. Turno 3 queda fuera de este MVP.
- Si un nombre asignado no tiene correo en catalogo, el guardado no falla; se registra warning en trazabilidad.
- `ausentismo.resumen_diario` debe enviarse siempre, incluso sin ausentes.
- Cuando no existan ausencias vigentes, el correo debe indicar: "no hay ausencias registradas".
- Todos los destinatarios se toman desde `FuncionarioCatalog`; no se permiten listas hardcodeadas.

### Variables minimas por plantilla

- Plantilla `turno_asignado`:
  - `fecha_turno`
  - `bloque_turno`
  - `slot_turno`
  - `nombre_asignado`
- Plantilla `ausentismo_resumen_diario`:
  - `fecha_reporte`
  - `total_ausentes`
  - `ausentes` (lista)
  - `mensaje_sin_novedades`

### Criterios de aceptacion de Sprint 1

- Existe definicion cerrada de disparadores, destinatarios, prioridad y SLA para ambos eventos MVP.
- Existe definicion de comportamiento para casos sin correo y sin ausentes.
- Existe definicion de variables minimas para plantillas V2.
- Queda habilitado el inicio de Sprint 2 sin decisiones pendientes de negocio para el MVP.

### Pendiente para Sprint 2 (tecnico)

- Definir llave de idempotencia final de `turno.asignado` por slot.
- Definir si la copia a jefatura se habilita por config global o por tipo de notificacion.
- Definir nomenclatura final de clases/namespaces del modulo V2.

---

## Sprint 2 - Diseno tecnico y modelo de datos V2

**Estado:** completado

### Objetivo

Definir arquitectura del modulo nuevo y esquema de persistencia.

### Tareas

- Definir estructura de carpetas del modulo V2 (ej. `app/NotificationsV2/...`).
- Disenar entidades y tablas:
  - `notification_types`
  - `notification_templates`
  - `notification_dispatches`
  - `notification_recipients`
  - `notification_attempts`
- Definir estados: `queued`, `processing`, `sent`, `failed`, `cancelled`.
- Definir idempotencia:
  - turno: por fecha + bloque + slot + asignado,
  - ausentismo diario: por fecha + tipo evento.
- Definir contratos base:
  - `NotificationOrchestrator`
  - `RecipientResolver`
  - `TemplateRenderer`
  - `ChannelSenderEmail`

### Entregables

- Documento tecnico V2.
- Modelo ER y flujo de envio.
- Contratos/interfaces listos para implementacion.

### Criterio de salida

Arquitectura y BD V2 aprobadas para construir.

### Decisiones tecnicas cerradas

- Namespace principal del modulo: `App\NotificationsV2`.
- Estructura base:
  - `app/NotificationsV2/Domain`
  - `app/NotificationsV2/Application`
  - `app/NotificationsV2/Infrastructure`
  - `app/NotificationsV2/Support`
- Configuracion del modulo en `config/notifications_v2.php`.
- Feature flag principal: `notifications_v2_enabled`.
- Politica de copia a jefatura para `turno.asignado`: controlada por tipo de notificacion (`notification_types.copy_contexts`) y desactivada por defecto.

### Modelo de datos V2 (definicion)

#### Tabla `notification_types`

- `id`
- `code` (unique) - ejemplos: `turno.asignado`, `ausentismo.resumen_diario`
- `name`
- `description` (nullable)
- `channel` (default: `mail`)
- `is_active` (boolean)
- `priority` (`high`, `normal`, `low`)
- `copy_contexts` (json nullable)
- timestamps

#### Tabla `notification_templates`

- `id`
- `notification_type_id` (fk)
- `version`
- `subject`
- `body_html`
- `body_text` (nullable)
- `variables_schema` (json)
- `is_active` (boolean)
- `activated_at` (nullable)
- timestamps

#### Tabla `notification_dispatches`

- `id`
- `notification_type_id` (fk)
- `template_id` (fk nullable)
- `event_key` (indice)
- `idempotency_key` (unique)
- `payload` (json)
- `status` (`queued`, `processing`, `sent`, `failed`, `cancelled`)
- `scheduled_for` (nullable)
- `queued_at` (nullable)
- `processed_at` (nullable)
- `sent_at` (nullable)
- `failed_at` (nullable)
- `failure_reason` (nullable)
- timestamps

#### Tabla `notification_recipients`

- `id`
- `dispatch_id` (fk)
- `type` (`to`, `cc`, `bcc`)
- `name` (nullable)
- `email`
- `context` (nullable)
- `status` (`pending`, `sent`, `failed`, `skipped`)
- `provider_message_id` (nullable)
- `failure_reason` (nullable)
- `sent_at` (nullable)
- timestamps

#### Tabla `notification_attempts`

- `id`
- `dispatch_id` (fk)
- `attempt_number`
- `channel`
- `status` (`processing`, `sent`, `failed`)
- `error_class` (nullable)
- `error_message` (nullable)
- `started_at`
- `finished_at` (nullable)
- timestamps

### Llaves de idempotencia definidas

- `turno.asignado`:
  - base: `turno.asignado|{fecha_turno}|{bloque_turno}|{slot_turno}|{nombre_asignado}`
  - hash final recomendado: `sha256(base)`.
- `ausentismo.resumen_diario`:
  - base: `ausentismo.resumen_diario|{fecha_reporte}`
  - hash final recomendado: `sha256(base)`.

### Contratos de aplicacion definidos

- `App\NotificationsV2\Application\Contracts\NotificationOrchestrator`
- `App\NotificationsV2\Application\Contracts\RecipientResolver`
- `App\NotificationsV2\Application\Contracts\TemplateRenderer`
- `App\NotificationsV2\Application\Contracts\ChannelSender`

### Flujo tecnico aprobado

1. Modulo origen dispara evento de dominio del caso de uso.
2. `NotificationOrchestrator` valida tipo activo y arma `idempotency_key`.
3. Si ya existe dispatch con misma llave, se aborta duplicado con estado trazable.
4. Si no existe, se crea `notification_dispatches` + `notification_recipients`.
5. Job en cola procesa envio por `ChannelSender` (mail).
6. Se persiste resultado en `notification_attempts` y estado final de dispatch.

### Criterios de aceptacion de Sprint 2

- Namespace, estructura de carpetas y contratos definidos para implementacion.
- Modelo de datos V2 cerrado con estados, FKs e indices clave.
- Estrategia de idempotencia definida para ambos eventos MVP.
- Regla de copia a jefatura definida y gobernada por configuracion.
- Queda habilitado Sprint 3 sin decisiones tecnicas pendientes de arquitectura.

### Pendiente para Sprint 3 (implementacion)

- Crear migraciones reales del modelo definido.
- Implementar contratos e infraestructura base del orquestador.
- Conectar pipeline de cola para envio por canal mail.

---

## Sprint 3 - Fundaciones backend del modulo V2

**Estado:** completado

### Objetivo

Construir el nucleo del sistema de envio paralelo.

### Tareas

- Crear migraciones y modelos del modulo V2.
- Implementar `NotificationOrchestrator`.
- Implementar `RecipientResolver` con integracion a `FuncionarioCatalog`.
- Implementar sender de email con colas (`ShouldQueue`) y retries/backoff.
- Registrar trazabilidad completa (dispatch, recipients, attempts).
- Asegurar ejecucion `afterCommit` cuando corresponda.

### Entregables

- Modulo V2 funcional para encolar y enviar correo.
- Evento de smoke test ejecutado end-to-end.

### Criterio de salida

Envio V2 exitoso en cola con trazabilidad persistida en BD.

### Implementacion realizada (base tecnica)

- Configuracion V2 agregada en `config/notifications_v2.php`.
- Variables de entorno agregadas en `.env.example` para feature flag, cola y scheduler.
- Migraciones creadas para:
  - `notification_types`
  - `notification_templates`
  - `notification_dispatches`
  - `notification_recipients`
  - `notification_attempts`
- Modelos de dominio creados en `App\NotificationsV2\Domain\Models`.
- Contratos de aplicacion creados en `App\NotificationsV2\Application\Contracts`.
- Implementaciones base creadas:
  - `DatabaseNotificationOrchestrator`
  - `CatalogRecipientResolver`
  - `SimpleTemplateRenderer`
  - `MailChannelSender`
  - `ProcessNotificationDispatchJob`
- Binding de interfaces en `AppServiceProvider` para resolver dependencias por contrato.
- Idempotencia implementada con `IdempotencyKeyFactory` para eventos MVP.

### Resultado tecnico de Sprint 3

- Pipeline V2 disponible para crear dispatch, resolver destinatarios, encolar job y registrar intentos.
- Trazabilidad persistente preparada para integraciones de Sprint 4 y Sprint 5.
- Base desacoplada de correos legacy, manteniendo implementacion paralela.

### Pendiente para Sprint 4

- Conectar `turno.asignado` desde `saveTurno1` y `saveTurno2Acd` al orquestador V2.
- Definir plantilla activa inicial de `turno_asignado` y carga de tipo/template en BD.

---

## Sprint 4 - Integracion de Turnos al modulo V2

**Estado:** completado

### Objetivo

Conectar asignaciones de turno al nuevo sistema de notificaciones.

### Tareas

- Emitir evento V2 desde `saveTurno1` y `saveTurno2Acd`.
- Resolver destinatario principal (funcionario asignado) por nombre->correo en catalogo.
- Configurar copia opcional por contexto (jefatura) sin hardcode.
- Crear plantilla editable `turno_asignado` (asunto/cuerpo).
- Registrar errores no bloqueantes cuando no exista correo para un nombre.

### Entregables

- Notificacion real de turno en cola.
- Plantilla de turno administrable en V2.

### Criterio de salida

Cada asignacion relevante de Turno 1 y Turno 2 ACD dispara notificacion V2 trazable.

### Implementacion realizada (Turnos)

- Integracion de notificaciones en `TurnoPage`:
  - `saveTurno1`
  - `saveTurno2Acd`
- Se compara estado previo vs nuevo para detectar slots realmente modificados.
- Solo se notifica cuando el slot cambia y tiene valor asignado.
- Se creo servicio dedicado `TurnoAssignmentNotifier` para aislar logica de negocio y mantener SRP.
- Se resuelve destinatario principal por nombre usando `FuncionarioCatalog::destinatarioByNombre(...)`.
- Si el nombre no tiene correo en catalogo:
  - no se bloquea el guardado de turno,
  - se registra warning en logs,
  - se crea dispatch V2 fallido en trazabilidad (`No valid recipients resolved for dispatch.`).
- Se deja bootstrap automatico de tipo/plantilla inicial para `turno.asignado`.

### Ajustes tecnicos complementarios

- `CatalogRecipientResolver` soporta tipo configurable para destinatarios de contexto (`to`, `cc`, `bcc`).
- `DatabaseNotificationOrchestrator` agrega soporte para `copy_contexts` definidos en `notification_types`.
- `DatabaseNotificationOrchestrator` marca como fallido el dispatch cuando no hay destinatarios validos.

### Validacion de Sprint 4

- Pruebas agregadas en `tests/Feature/NotificationsV2/TurnoAssignedNotificationTest.php`:
  - caso con funcionario con correo valido -> dispatch `queued` + recipient `pending`.
  - caso sin correo en catalogo -> dispatch `failed` con razon trazable.

### Pendiente para Sprint 5

- Implementar comando/scheduler de `ausentismo.resumen_diario` a las 08:05 (`America/Santiago`).
- Resolver destinatarios diarios desde todo el `FuncionarioCatalog` con correo valido.
- Enviar correo "no hay ausencias registradas" cuando no existan ausentes vigentes.

---

## Sprint 5 - Integracion de Ausentismo diario al modulo V2

**Estado:** completado

### Objetivo

Automatizar envio diario de funcionarios no disponibles.

### Tareas

- Crear comando programado (ej. `notificaciones:ausentismo-diario`).
- Programar scheduler a las 08:05 AM con zona `America/Santiago`.
- Construir consulta de ausencias vigentes para la fecha de ejecucion.
- Resolver destinatarios desde `FuncionarioCatalog` (todos con correo valido).
- Crear plantilla editable `ausentismo_resumen_diario`.
- Implementar envio obligatorio de correo cuando no haya ausentes con mensaje "no hay ausencias registradas".

### Entregables

- Envio diario automatico operativo.
- Trazabilidad por corrida diaria.

### Criterio de salida

Resumen diario enviado automaticamente a destinatarios configurados.

### Implementacion realizada (Ausentismo diario)

- Comando creado: `notificaciones:ausentismo-diario`.
- Scheduler configurado en `routes/console.php`:
  - ejecucion diaria a las 08:05,
  - zona `America/Santiago`,
  - `withoutOverlapping`.
- Servicio dedicado creado: `DailyAusentismoSummaryNotifier`.
- Integracion con orquestador V2 para emitir `ausentismo.resumen_diario`.
- Destinatarios resueltos desde todo el catalogo (`use_all_catalog = true`).
- Payload diario incluye:
  - `fecha_reporte`
  - `total_ausentes`
  - `ausentes`
  - `mensaje_sin_novedades`
- Regla de negocio implementada: si no hay ausentes, se envia correo de control con "no hay ausencias registradas".
- Bootstrap automatico de tipo/plantilla inicial para `ausentismo.resumen_diario`.

### Validacion de Sprint 5

- Pruebas agregadas en `tests/Feature/NotificationsV2/AusentismoDailySummaryCommandTest.php`:
  - caso con ausentes: genera dispatch y destinatarios de todo el catalogo.
  - caso sin ausentes: genera dispatch con mensaje de control.

### Pendiente para Sprint 6

- Completar pruebas de integracion de envio (queue/mail fakes) a nivel orquestador/job.
- Definir checklist operativo final de monitoreo y rollout gradual.

---

## Sprint 6 - Observabilidad, pruebas y rollout controlado

**Estado:** completado

### Objetivo

Cerrar calidad operativa y salida incremental.

### Tareas

- Logging estructurado (`dispatch_id`, `event_key`, `recipient`, `status`).
- Tests unitarios de orquestador/resolvers.
- Tests de integracion con `Queue::fake` y `Mail::fake`.
- Tests de idempotencia y reintentos.
- Activacion por feature flag `notifications_v2_enabled`.
- Plan de despliegue gradual por evento (`turno` y luego `ausentismo`).

### Entregables

- Suite de pruebas del MVP V2.
- Checklist de despliegue y rollback.
- Evidencia de monitoreo operativo basico.

### Criterio de salida

MVP V2 estable en produccion con activacion controlada.

### Implementacion realizada (observabilidad y calidad)

- Logging estructurado agregado en puntos criticos:
  - `notifications_v2.dispatch.created`
  - `notifications_v2.dispatch.duplicate`
  - `notifications_v2.dispatch.no_recipients`
  - `notifications_v2.job.processing`
  - `notifications_v2.job.sent`
  - `notifications_v2.job.retry`
  - `notifications_v2.job.failed`
  - `notifications_v2.mail.recipient_sent`
  - `notifications_v2.mail.recipient_failed`
- Feature flag aplicada en orquestador para controlar encolado real:
  - `notifications_v2.enabled`
- Integracion por cola validada para escenario habilitado/deshabilitado.

### Pruebas incorporadas en Sprint 6

- `tests/Feature/NotificationsV2/NotificationOrchestratorTest.php`:
  - encola job cuando feature flag esta activa,
  - no encola job cuando feature flag esta desactivada,
  - valida idempotencia para payload repetido.
- `tests/Feature/NotificationsV2/ProcessNotificationDispatchJobTest.php`:
  - flujo exitoso: dispatch/recipient/attempt en estado `sent`,
  - flujo con error: retry dejando `queued` y transicion final a `failed` en `failed()`.

### Checklist de rollout controlado

1. Deploy con `notifications_v2.enabled=false`.
2. Ejecutar migraciones en produccion (`php artisan migrate --force`).
3. Verificar existencia de tipo/template para:
   - `turno.asignado`
   - `ausentismo.resumen_diario`
4. Activar feature flag en ventana controlada.
5. Ejecutar validacion manual:
   - cambio en Turno 1 o Turno 2 ACD,
   - comando `notificaciones:ausentismo-diario --date=YYYY-MM-DD`.
6. Revisar logs estructurados y estados en tablas V2.
7. Si hay falla operativa, volver flag a `false` y mantener trazabilidad para analisis.

### Pendiente para Sprint 7

- Integrar progresivamente los correos legacy al orquestador V2 por adaptadores.

---

## Sprint 7 - Integracion progresiva de correos legacy (fase posterior)

**Estado:** completado (oleada 1)

### Objetivo

Migrar flujos de correo actuales al orquestador V2 sin interrupcion operativa.

### Tareas

- Crear adaptadores para flujos legacy.
- Migrar por fases y validar paridad funcional.
- Retirar envios directos gradualmente en UI/controladores.

### Entregables

- Flujos legacy operando en V2.
- Documento de deprecacion del esquema legacy.

### Criterio de salida

Todos los correos del sistema gestionados por el modulo centralizado.

### Avance implementado (oleada 1)

- Adaptador legacy implementado para correos de estado de cuenta:
  - `LegacyUserAccountStatusNotifier`
- Integraciones migradas a V2:
  - `SeguridadController::sendStatusEmail(...)`
  - `UsersRolesPage::sendStatusEmail(...)`
- Comportamiento del adaptador:
  - con `notifications_v2.enabled=false`: mantiene envio legacy con `UserAccountStatusNotification`,
  - con `notifications_v2.enabled=true`: enruta por orquestador V2 (`legacy.user_account_status`) y mantiene trazabilidad.
- Bootstrap automatico de tipo/plantilla para `legacy.user_account_status`.

### Pruebas de oleada 1

- `tests/Feature/NotificationsV2/LegacyUserAccountStatusNotifierTest.php`:
  - fallback legacy cuando V2 esta desactivado,
  - ruteo V2 cuando la feature flag esta activa.

### Modelos de correo Outlook + rutas de preview

- Modelos HTML (compatibles con Outlook, basados en tablas e inline CSS):
  - `resources/views/emails/v2/turno-asignado-outlook.blade.php`
  - `resources/views/emails/v2/ausentismo-resumen-diario-outlook.blade.php`
  - `resources/views/emails/v2/legacy-user-status-outlook.blade.php`
- Rutas de preview (requieren `auth` + `can:navbar.seguridad.visible`):
  - `GET /preview/notificaciones-v2/turno-asignado`
  - `GET /preview/notificaciones-v2/ausentismo-resumen` (usar `?empty=1` para vista sin ausentes)
  - `GET /preview/notificaciones-v2/user-status` (usar `?status=approved|rejected|suspended|unsuspended`)

### Issues futuros (backlog)

- Migrar flujo legacy de Programacion Diaria al orquestador V2 (oleada 2).
- Consolidar adaptadores restantes y retirar llamadas directas legacy en controladores/componentes pendientes.

## Riesgos y mitigaciones

- Riesgo: duplicidad de envios por guardados repetidos.
  - Mitigacion: idempotencia por llave de evento + restricciones unicas.
- Riesgo: nombre de turno sin correo asociado.
  - Mitigacion: no bloquear proceso; registrar warning y tablero de pendientes.
- Riesgo: latencia o saturacion en envios.
  - Mitigacion: colas obligatorias + monitoreo de workers + retries.
- Riesgo: resumen diario sin destinatarios validos.
  - Mitigacion: contexto explicito en config + validacion previa de correos.
- Riesgo: cambios no controlados en produccion.
  - Mitigacion: feature flags + rollout gradual por subsistema.

## Indicadores de exito del MVP

- >95% de envios exitosos en primer intento.
- 100% de eventos `turno.asignado` trazables por `dispatch_id`.
- 100% de corridas de `ausentismo.resumen_diario` registradas (con o sin novedades).
- <5 minutos de latencia promedio en envios no criticos.
- 0 regresiones en flujos legacy durante fase paralela.
