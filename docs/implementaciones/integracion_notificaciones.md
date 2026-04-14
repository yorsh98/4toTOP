# Integracion del Sistema de Notificaciones por Correo

## Objetivo

Implementar un sistema centralizado de notificaciones por correo para la plataforma, desacoplado de los envios actuales, con arquitectura escalable y preparada para integrar los flujos legacy en una fase posterior.

## Alcance inicial

- Incluir solo el nuevo sistema de notificaciones (no migrar aun los correos existentes).
- Disparar correos en eventos funcionales del sistema (alta/modificacion/cambio de estado).
- Soportar envio asincrono con colas, trazabilidad y reintentos.
- Dejar base para integrar mas adelante los envios actuales.

## Principios de diseno

- SOLID: responsabilidades separadas por capa.
- Event-driven: el dominio dispara eventos; el sistema de notificaciones reacciona.
- Idempotencia: evitar duplicados por evento.
- Observabilidad: cada envio debe ser auditable.
- Configurable: plantillas, destinatarios y reglas sin hardcode innecesario.

## Plan por sprints

## Sprint 1 - Descubrimiento y definicion funcional

### Objetivo

Definir formalmente que se notificara, cuando, a quien y con que prioridad.

### Tareas

- Levantar catalogo de eventos notificables del sistema.
- Definir matriz de destinatarios por evento (actor, supervisor, listas dinamicas, etc.).
- Definir criticidad/prioridad por evento (alta, normal, baja).
- Definir SLA de envio (inmediato, diferido, agrupado).
- Alinear reglas de negocio con usuarios clave.

### Entregables

- Documento funcional de eventos notificables.
- Matriz evento -> destinatarios -> canal -> prioridad.
- Criterios funcionales de aceptacion.

### Criterio de salida

Todos los eventos iniciales del MVP definidos y aprobados.

---

## Sprint 2 - Diseno tecnico y modelo de datos

### Objetivo

Disenar la arquitectura tecnica y persistencia de notificaciones.

### Tareas

- Definir arquitectura por capas (Domain, Application, Infrastructure).
- Disenar entidades y tablas:
  - notification_types
  - notification_templates
  - notification_dispatches
  - notification_recipients
  - notification_attempts
- Definir estados de ciclo de vida: queued, processing, sent, failed, cancelled.
- Definir estrategia de idempotencia (hash/unique key por evento).
- Definir contratos/interfaces base (orquestador, resolvers, sender, renderer).

### Entregables

- Documento tecnico de arquitectura.
- Diagrama simple de flujo de envio.
- Modelo ER y convenciones de datos.

### Criterio de salida

Arquitectura y esquema de base aprobados para implementacion.

---

## Sprint 3 - Fundaciones backend (MVP tecnico)

### Objetivo

Construir el nucleo del sistema y pipeline minimo de envio.

### Tareas

- Crear migraciones y modelos del modulo de notificaciones.
- Implementar NotificationOrchestrator (capa aplicacion).
- Implementar resolvers base:
  - RecipientResolver
  - TemplateRenderer
  - ChannelSenderEmail
- Implementar eventos/listeners para disparo.
- Implementar jobs en cola con retry y backoff.
- Asegurar envio afterCommit para no notificar en transacciones revertidas.

### Entregables

- Modulo funcional capaz de encolar y enviar notificaciones por email.
- Primer evento de prueba integrado end-to-end.

### Criterio de salida

Envio exitoso en cola para al menos 1 evento real, con registro en BD.

---

## Sprint 4 - Plantillas y administracion operativa

### Objetivo

Habilitar gestion y personalizacion de notificaciones sin tocar codigo.

### Tareas

- Crear gestion de tipos de notificacion (activa/inactiva).
- Crear gestion de plantillas (subject/body + variables permitidas).
- Implementar preview de plantilla y envio de prueba.
- Implementar reglas de validacion de variables y sanitizacion.
- Agregar permisos RBAC para administracion del modulo.

### Entregables

- CRUD operativo de tipos y plantillas.
- Preview funcional de notificaciones.
- Seguridad de acceso por roles.

### Criterio de salida

Equipo funcional puede crear/editar plantillas y probar envios sin despliegue.

---

## Sprint 5 - Observabilidad, errores y hardening

### Objetivo

Fortalecer confiabilidad, trazabilidad y soporte operativo.

### Tareas

- Logging estructurado (dispatch_id, event_key, recipient, resultado).
- Registro de intentos/fallos y motivo tecnico.
- Dashboard basico de monitoreo (pendientes, enviados, fallidos).
- Alertas basicas por tasa de fallo.
- Politica de reintentos y manejo de errores no recuperables.

### Entregables

- Monitoreo base de salud del sistema de notificaciones.
- Flujo de soporte para diagnosticar envios fallidos.

### Criterio de salida

Fallas detectables y trazables sin revisar logs manuales extensos.

---

## Sprint 6 - Pruebas, estabilizacion y rollout controlado

### Objetivo

Validar calidad y habilitar salida gradual a produccion.

### Tareas

- Tests unitarios de servicios y resolvers.
- Tests de integracion con Queue fake / Mail fake.
- Tests de idempotencia y reintentos.
- Activacion mediante feature flag (`notifications_v2_enabled`).
- Rollout gradual por tipo de evento.

### Entregables

- Suite de pruebas del modulo.
- Plan de despliegue controlado.
- Checklist de rollback.

### Criterio de salida

Sistema listo para uso productivo incremental.

---

## Sprint 7 - Integracion progresiva de correos legacy (fase posterior)

### Objetivo

Migrar envios actuales al nuevo orquestador sin interrumpir operacion.

### Tareas

- Crear adaptadores para flujos existentes.
- Migrar por fases cada flujo legacy.
- Medir paridad funcional entre sistema antiguo y nuevo.
- Retirar llamadas directas a Mail/Notification en capas de UI/controlador.

### Entregables

- Flujos legacy integrados al sistema centralizado.
- Documento de deprecacion de envio legacy.

### Criterio de salida

Todos los correos operan desde el nuevo sistema central.

## Riesgos y mitigaciones

- Riesgo: duplicidad de envios por eventos repetidos.
  - Mitigacion: clave de idempotencia por evento + constraints unicos.
- Riesgo: latencia o bloqueo en envio.
  - Mitigacion: colas obligatorias + monitoreo de workers.
- Riesgo: plantillas con variables invalidas.
  - Mitigacion: validacion de placeholders y preview antes de activar.
- Riesgo: cambios funcionales no controlados.
  - Mitigacion: feature flags + rollout gradual por evento.

## Indicadores de exito

- >95% de envios exitosos en primer intento.
- <5 min de latencia promedio en notificaciones no criticas.
- 100% de notificaciones trazables por `dispatch_id`.
- Reduccion progresiva de envios directos legacy hasta su eliminacion.
