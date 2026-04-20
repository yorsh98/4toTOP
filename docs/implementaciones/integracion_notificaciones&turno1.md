# Integracion Notificaciones V2 y Turno 1

## Objetivo

Conectar el flujo de Turno 1 con Notificaciones V2 para que:

- la maqueta de correo (preview) se alimente con datos reales del sistema,
- el envio semanal llegue solo a personas seleccionadas en Turno 1,
- el usuario autenticado que ejecuta el envio reciba copia de evidencia.

## Alcance

- Subsistema: Turnos (Turno 1 semanal).
- Evento: `turno1.resumen_semanal`.
- Preview objetivo: `/preview/notificaciones-v2/turno-asignado`.

Fuera de alcance:

- Turno 3 (queda en Programacion de Audiencias).
- Migracion adicional de otros correos legacy en esta fase.

## Reglas funcionales confirmadas

1. El resumen semanal se envia solo a personas seleccionadas en Turno 1:
   - Juez del turno (TM1)
   - Jefe de turno (TJ1)
   - Funcionarios del equipo (TF1, TF2, TF3)
2. Todos los destinatarios se resuelven por nombre usando `FuncionarioCatalog`.
3. Si un seleccionado no tiene correo valido, no se bloquea el proceso; se registra warning.
4. Se deduplican destinatarios por email.
5. Se agrega como destinatario al usuario autenticado que ejecuta el envio (evidencia).

## Fuente unica de datos (preview + envio)

Se define una sola capa de armado de datos para evitar diferencias entre lo que se ve y lo que se envia.

### Propuesta tecnica

- Crear/ajustar metodo de armado en servicio de notificacion semanal (ej. `buildWeekData(...)`).
- El metodo debe retornar:
  - payload completo de la tabla,
  - destinatarios finales resueltos.
- El preview y el envio deben consumir ese mismo metodo.

## Flujo integrado

1. Usuario edita Turno 1 en `TurnoPage`.
2. Usuario guarda Turno 1 (`saveTurno1`).
3. Usuario ejecuta envio semanal (`sendTurno1Summary`).
4. Notificador semanal arma payload real desde tablas `turno` + catalogo de funcionarios.
5. Resolver genera destinatarios:
   - seleccionados TM1/TJ1/TF1/TF2/TF3,
   - + usuario autenticado (actor).
6. Orquestador V2 crea dispatch + recipients + attempt.
7. Job en cola envia correo y actualiza trazabilidad.

## Integracion del preview

### Objetivo

La ruta `/preview/notificaciones-v2/turno-asignado` debe reflejar datos reales de Turno 1, no datos demo.

### Implementacion esperada

- El controller de preview delega en el mismo servicio/builder del envio semanal.
- Soporta consulta opcional de fecha base para probar semanas (`?date=Y-m-d`).
- Si faltan datos, mostrar placeholders coherentes sin romper la vista.

## Integracion de destinatarios

### Destinatarios principales

- Lista dinamica por seleccion de campos:
  - `TM1`, `TJ1`, `TF1`, `TF2`, `TF3`.

### Destinatario de evidencia

- Usuario autenticado (`auth()->user()->email`) agregado como destinatario adicional `to`.
- Si el email del actor ya existe en la lista, no se duplica.

### Validaciones

- Ignorar nombres vacios.
- Ignorar correos vacios o invalidos.
- Loggear seleccionados sin correo.

## Modelo de envio recomendado

- Modo: encolado (cola V2).
- Idempotencia semanal: `turno1.resumen_semanal|fecha_inicio_semana|hash_contenido`.
- Registro obligatorio en:
  - `notification_dispatches`
  - `notification_recipients`
  - `notification_attempts`

## Plan de ejecucion

### Etapa 1 - Unificacion de datos

- Extraer builder unico de payload+recipients para Turno 1 semanal.
- Reusar en preview y envio.

Estado: implementado

Implementado en `TurnoOneWeeklySummaryNotifier` con `buildWeekSummaryData(...)`.

### Etapa 2 - Destinatarios reales

- Reemplazar contextos globales para este flujo por seleccionados del Turno 1.
- Agregar actor autenticado como evidencia.

Estado: implementado

Implementado en `TurnoOneWeeklySummaryNotifier`:

- Destinatarios calculados desde `TM1`, `TJ1`, `TF1`, `TF2`, `TF3`.
- Resolucion por nombre con `FuncionarioCatalog::destinatarioByNombre(...)`.
- Inclusion del usuario autenticado (`auth()->user()->email`) como evidencia.
- Deduccion de duplicados por email.
- Warning en logs para seleccionados sin correo valido.

### Etapa 3 - Ajuste de preview

- Conectar preview al builder real.
- Eliminar hardcode demo del preview de turno.

Estado: implementado

Implementado en `NotificationsV2PreviewController::turnoAsignado(...)`:

- El preview consume `buildWeekSummaryData(...)`.
- Se elimino la maqueta hardcode de datos demo para Turno 1.
- Se soporta `?date=Y-m-d` para pruebas de semana.

### Etapa 4 - QA funcional

- Caso completo con datos y correos reales.
- Caso con destinatarios repetidos.
- Caso con seleccionado sin correo.
- Confirmar inclusion del actor autenticado.

Estado: implementado

Cobertura ejecutada:

- Preview real con datos del sistema y soporte `?date=Y-m-d`.
- Destinatarios del resumen semanal solo desde seleccionados (`TM1`, `TJ1`, `TF1-3`) + actor autenticado.
- Exclusion de seleccionados sin correo valido (warning sin romper flujo).
- Deduccion de duplicados por email en destinatarios.
- Validacion de que cambios solo en campos `*_CARGO` no disparan notificaciones de asignacion.

Pruebas asociadas:

- `tests/Feature/NotificationsV2/NotificationsV2PreviewRoutesTest.php`
- `tests/Feature/NotificationsV2/TurnoOneWeeklySummaryRecipientsTest.php`
- `tests/Feature/NotificationsV2/TurnoOneWeeklySummaryCommandTest.php`
- `tests/Feature/NotificationsV2/TurnoAssignedNotificationTest.php`

## Criterios de aceptacion

- El preview de turno muestra datos reales de Turno 1.
- El correo semanal llega a TM1, TJ1, TF1-3 segun seleccion y correos validos.
- El usuario que ejecuta el envio recibe copia de evidencia.
- No hay destinatarios duplicados.
- El proceso no falla por un seleccionado sin correo.
- El dispatch queda trazado en V2 con recipients y attempt.

## Riesgos y mitigaciones

- Riesgo: nombres ambiguos/homonimos en catalogo.
  - Mitigacion: resolver por nombre exacto y registrar ambiguedad; mejorar luego con ID de funcionario.
- Riesgo: actor sin correo en cuenta de usuario.
  - Mitigacion: warning y continuar envio a seleccionados.
- Riesgo: divergencia preview/envio por doble logica.
  - Mitigacion: builder unico obligatorio para ambos.

## Checklist operativo

- [ ] Feature flag `notifications_v2.enabled` validada para entorno.
- [ ] Turno 1 configurado con semana y personas.
- [ ] Preview validado visualmente en ruta de notificaciones V2.
- [ ] Envio semanal ejecutado y revisado en tablas V2.
- [ ] Correo recibido por seleccionados + actor autenticado.
