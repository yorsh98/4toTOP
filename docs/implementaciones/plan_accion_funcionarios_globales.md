# Plan de Accion por Sprint - Catalogo Global de Funcionarios (Reset de tabla)

## Objetivo general

Implementar un catalogo global de funcionarios como fuente unica para los subsistemas de:

- Programacion Diaria (Audiencias)
- Turnos (1, 2 y 3)
- Oficio/Libertad
- Integraciones futuras de notificaciones por correo y modulo de cumpleanos

Este plan incorpora la decision operativa de **eliminar la tabla actual de funcionarios y recrearla** con la estructura final, ya que hay pocos registros y se cargara informacion desde cero.

---

## Decisiones cerradas

- Modelo de roles: **un rol principal por funcionario**.
- Estrategia de datos: **reset de tabla `funcionarios`** + recarga manual inicial.
- Volumen estimado de carga inicial: ~25 funcionarios.
- Compatibilidad: los modulos legacy siguen guardando texto en sus tablas (`UserSolicitante`, campos de turno y audiencias) durante esta fase.

---

## Datos y catalogos base

### Campos obligatorios en funcionarios

- `nombre` (nombre completo)
- `rol`
- `email`
- `fecha_cumpleanos`

### Roles oficiales v1

- `juez`
- `sala_causa`
- `ttypp`
- `encargado_causa`
- `jefatura`
- `informatico`

### Matriz rol -> vista -> campo

| Rol | Aparece en | Campo destino |
|---|---|---|
| juez | Programacion Diaria; Turno 1,2,3 | Juez Presidente, Juez Redactor, Juez Integrante; Magistrado 1,2,3 |
| sala_causa | Programacion Diaria; Turno 1,2,3; Oficio/Libertad | Acta, Anfitrion; Funcionario 1,2,3; Solicitante |
| ttypp | Programacion Diaria; Turno 1,2,3; Oficio/Libertad | Encargado TTP, Encargado TTP Zoom; Funcionario 1,2,3; Solicitante |
| encargado_causa | Programacion Diaria; Turno 1,2; Oficio/Libertad | Encargado de Causa; Jefatura 1; Solicitante |
| jefatura | Turno 1,2; Oficio/Libertad | Jefatura 1; Solicitante |
| informatico | Turno 1,2; Oficio/Libertad | Funcionario 1,2,3; Solicitante |

---

## Principios de implementacion

- `funcionarios` es la unica fuente de personas por rol.
- No hardcodear nombres en controladores, componentes ni vistas.
- Validar en backend que cada campo reciba solo roles permitidos por contexto.
- Preparar consultas de correos y cumpleanos para futuros modulos.
- Despliegue con backup y rollback documentado.

---

## Plan por sprints

## Sprint 0 - Preparacion de produccion y respaldo (obligatorio)

### Objetivo

Ejecutar el reset de `funcionarios` de forma controlada, con evidencia y posibilidad de rollback.

### Tareas

1. Respaldar `funcionarios` de produccion (CSV y/o SQL).
2. Guardar evidencia de uso actual (capturas de vistas/inputs que hoy dependen de funcionarios).
3. Definir ventana de mantenimiento corta para aplicar migracion.
4. Preparar rollback:
   - Script para restaurar backup.
   - Validacion post-restore.

### Entregables

- Backup versionado de la tabla antigua.
- Checklist de rollback aprobado.
- Runbook operativo de ejecucion: `docs/implementaciones/sprint0_runbook_funcionarios.md`.

### Criterio de salida

Se puede volver al estado anterior en minutos si algo falla.

---

## Sprint 1 - Reset de tabla y nuevo modulo Funcionarios

### Objetivo

Recrear `funcionarios` con estructura final y dejar lista la UI para carga manual completa.

### Tareas

1. Crear migracion de reset de tabla:
   - `dropIfExists('funcionarios')`
   - `create('funcionarios')` con columnas nuevas.
2. Definir constraints:
   - `email` unique.
   - `rol` validado por catalogo cerrado en aplicacion.
3. Actualizar `App\Models\Funcionario` (`fillable`, `casts`).
4. Refactor de `addfuncionarioController`:
   - Validacion de nombre, rol, email, fecha_cumpleanos.
5. Redisenar `resources/views/addfuncionario.blade.php`:
   - Formulario de 4 campos.
   - Tabla con columnas nuevas.
   - Mensajes de error y exito claros.

### Entregables

- Tabla recreada y operativa.
- CRUD de funcionarios alineado al nuevo modelo.

### Criterio de salida

Se pueden crear/listar/eliminar funcionarios con los 4 campos obligatorios en el nuevo esquema.

---

## Sprint 2 - Carga manual inicial y validacion de calidad

### Objetivo

Cargar los ~25 funcionarios con datos completos y consistentes.

### Tareas

1. Cargar funcionarios manualmente desde la vista de administracion.
2. Verificar duplicados de email y formato de fechas.
3. Revisar cobertura de roles (que todos los roles tengan al menos un funcionario segun operacion real).
4. Crear reporte corto de control de calidad de datos.

### Entregables

- Catalogo inicial poblado y validado.
- Registro de calidad de carga inicial.

### Criterio de salida

Catalogo completo para iniciar integracion en subsistemas sin datos pendientes criticos.

---

## Sprint 3 - Capa global de roles y consultas reutilizables

### Objetivo

Centralizar en codigo los roles y filtros por contexto para que todos los modulos consuman la misma logica.

### Tareas

1. Crear catalogo comun de roles y contextos (config/servicio).
2. Exponer metodos reutilizables:
   - `nombresPorRoles([...])`
   - `funcionariosPorContexto('...')`
   - `emailsPorContexto('...')`
3. Definir contextos estables:
   - `audiencias.jueces`
   - `audiencias.acta_anfitrion`
   - `audiencias.ttp`
   - `audiencias.encargado_causa`
   - `turnos.magistrados`
   - `turnos.funcionarios`
   - `turnos.jefatura`
   - `oficios.solicitantes`
4. Eliminar listas hardcodeadas existentes.

### Entregables

- Capa unica de consulta por roles/contextos.

### Criterio de salida

No hay listas de nombres en duro en los modulos intervenidos.

---

## Sprint 4 - Integracion con Oficio/Libertad

### Objetivo

Usar funcionarios por rol para el campo solicitante de nuevo numero.

### Tareas

1. Ajustar `SistOficioLibertadesController` para cargar solicitantes por roles:
   - `sala_causa`, `ttypp`, `encargado_causa`, `jefatura`, `informatico`.
2. Mantener persistencia actual de texto (`UserSolicitante`).
3. Validar solicitante contra lista dinamica filtrada por rol.

### Entregables

- Oficio/Libertad consumiendo catalogo global en solicitantes.

### Criterio de salida

Solo roles permitidos aparecen y el flujo sigue operativo.

---

## Sprint 5 - Integracion con Turnos

### Objetivo

Parametrizar los campos de Turnos por rol desde el catalogo global.

### Tareas

1. Integrar en `TurnoController` + `turno.blade.php`:
   - Magistrados -> `juez`
   - Funcionarios -> `sala_causa`, `ttypp`, `informatico`
   - Jefatura -> `encargado_causa`, `jefatura`
2. Mantener columnas legacy de `turno` sin cambio de estructura en esta fase.
3. Validar backend para impedir asignaciones fuera de rol.

### Entregables

- Turnos con listas dinamicas por rol.

### Criterio de salida

Asignaciones de turno validadas por rol y visibles en vistas existentes.

---

## Sprint 6 - Integracion con Programacion Diaria (Audiencias)

### Objetivo

Conectar campos de personas en audiencias al catalogo global por rol.

### Tareas

1. `AudienciaForm`:
   - JuezP/JuezR/JuezI -> `juez`
   - Acta/Anfitrion -> `sala_causa`
   - Encargado TTP/TTP Zoom -> `ttypp`
   - Encargado de Causa -> `encargado_causa`
2. `AudienciasCortasBatch` y `LecturasSentenciaBatch`:
   - reemplazar `tiposencargados` hardcodeado por consultas al catalogo.
3. Mantener persistencia de strings en tabla `audiencias` (compatibilidad).

### Entregables

- Formularios de audiencias con datos de funcionarios reales por rol.

### Criterio de salida

Campos de personas en audiencias responden a catalogo global y matriz funcional.

---

## Sprint 7 - Preparacion de notificaciones/cumpleanos + QA final

### Objetivo

Cerrar la implementacion dejando base lista para subsistemas futuros y salida estable.

### Tareas

1. Exponer consultas para futuro:
   - `emailsPorRol(...)`
   - `emailsPorContexto(...)`
   - `cumpleanerosDelDia(fecha)`
2. Documentar contrato de consumo para otros modulos.
3. Ejecutar QA transversal:
   - Funcionarios
   - Oficio/Libertad
   - Turnos
   - Audiencias
4. Validar calidad:
   - `./vendor/bin/pint --test`
   - pruebas focalizadas de cambios

### Entregables

- Capa lista para integracion de correos y cumpleanos.
- Evidencia de pruebas y checklist de salida.

### Criterio de salida

Implementacion estable, sin regresiones funcionales criticas, con base reutilizable para subsistemas futuros.

---

## Riesgos y mitigaciones

- Riesgo: perdida de datos al resetear tabla.
  - Mitigacion: backup previo + rollback probado en Sprint 0.
- Riesgo: errores de carga manual inicial.
  - Mitigacion: validaciones estrictas + checklist de calidad de datos.
- Riesgo: desalineacion entre roles y vistas.
  - Mitigacion: matriz funcional unica + validaciones backend por contexto.
- Riesgo: hardcode residual en componentes.
  - Mitigacion: auditoria tecnica por grep y refactor en Sprints 3-6.

## Indicadores de exito

- 100% de funcionarios activos con nombre, rol, email y fecha_cumpleanos.
- 0 listas hardcodeadas de nombres en modulos intervenidos.
- 100% de campos de personas en Oficio/Libertad, Turnos y Audiencias alimentados por catalogo global.
- Base lista para disparar notificaciones por correo y sistema de cumpleanos.
