# Integracion TTYPP (Unidad de Testigos y Peritos)

## Objetivo
Reemplazar el Excel operativo de Testigos y Peritos por un formulario web en backoffice, con datos persistidos en base de datos, todos los campos opcionales (`nullable`), y visualizacion del avance en una nueva pestana de `welcome`, siguiendo el estilo de la vista de Programacion de Audiencias.

## Alcance funcional inicial
- Crear modulo `TTYPP` en backoffice.
- Implementar formulario basado en la planilla mostrada (columnas tipo: RIT, Tipo AUD, N Dias, Cant., Modalidad, Encargadas, RUC, Acta, Sala).
- Permitir crear, editar, listar y filtrar registros.
- Todos los inputs deben aceptar `null` (sin obligatorios en primera etapa).
- Exponer una nueva pestana en `welcome` para visualizar avance del modulo (estado de implementacion y metricas basicas).

## Supuestos
- Se trabaja con Laravel 13 + Livewire 3/Volt ya existente en el proyecto.
- El menu `TTYPP` ya existe y sera conectado al modulo real en estos sprints.
- Seguridad y permisos se integran con el esquema actual (`spatie/laravel-permission`).

## Estado actual (actualizado)
- Sprint 1 completado: modulo base creado en backoffice con ruta `ttypp.index`, permisos `navbar.ttypp.*` y `ttypp.*`, migracion, modelo y factory.
- Sprint 2 en progreso avanzado: CRUD funcional con campos `nullable`, busqueda, paginacion y acciones de editar/eliminar.
- Sprint 3 completado: modulo `TTyPP` publicado en `welcome`, con visualizacion diaria de `Listado TTYPP` y barra superior para navegar por fechas.
- Sprint 4 completado (fase inicial):
  - Importacion Excel SIAGJ (`.xlsx` y `.xls`) con filtros operativos y tolerancia a campos vacios.
  - Exportacion Excel `.xlsx` del listado filtrado.
  - Auditoria minima por registro (`created_by`, `updated_by`).
  - Alertas no bloqueantes de calidad de datos (RUC, Acta, Modalidad).
- Sprint 5 completado (endurecimiento inicial):
  - Permisos granulares para TTYPP: `ttypp.importar` y `ttypp.exportar`.
  - Aplicacion de permisos en UI y acciones Livewire de importacion/exportacion.
  - Pruebas Pest para flujo principal: alta nullable, filtro, permisos de importacion Excel SIAGJ, importacion filtrada, exportacion `.xlsx` y ruta de modulo `TTyPP` en `welcome`.
- Integracion de correo TTyPP completada:
  - Botón `Remitir correo` en `Listado TTYPP`.
  - Servicio `TtyppListadoNotifier` con plantilla `ttypp.listado.diario`.
  - Destinatarios resueltos desde catalogo de funcionarios con rol `TTyPP`.
  - Pruebas Pest del envío de correo TTyPP.
- Importacion SIAGJ completada:
  - Archivo esperado `.xlsx` o `.xls` con encabezados `RIT`, `AÑOCAUSA`, `TIPOCAUSA`, `TIPO_TRAMITE`, `EVENTO`, `FECHAEVENTO`, `ESTADOEVENTO`.
  - Solo se importan filas con `ESTADOEVENTO = Por realizarse`, `EVENTO = Audiencia de juicio oral TOP` y `TIPO_TRAMITE = Audiencia`.
  - Se guarda `rit` como `RIT-AÑOCAUSA` y `fecha_asignacion` desde `FECHAEVENTO`; el resto queda editable desde backoffice.
- Ajustes funcionales ya aplicados:
  - Se elimino el campo `Anio` del formulario y del esquema TTYPP.
  - `RIT` autocompleta `RUC` consultando tabla `audiencias`.
  - Campos `Encargada TI MP`, `Encargada TI D`, `Encargada Zoom` usan `datalist` con funcionarios rol `TTyPP`.
  - Campo `Acta` usa `datalist` con funcionarios rol `Sala/Causa`.

---

## Sprint 1 - Descubrimiento, modelo de datos y base tecnica
**Objetivo:** Definir estructura del Excel en dominio TTYPP y dejar base de desarrollo lista.

### Entregables
- Levantamiento de campos de la planilla y reglas de negocio minimas.
- Diseno de tabla principal `ttypp_registros` con columnas `nullable`.
- Migracion inicial + modelo Eloquent + factory.
- Rutas base del modulo en backoffice.
- Vista placeholder del modulo TTYPP.

### Tareas
- Mapear cada columna del Excel a campo DB (tipos sugeridos: `string`, `unsignedSmallInteger`, `text`, `date`, segun corresponda).
- Definir convenciones de catalogos futuros (ej: modalidad, tipo_audiencia), sin bloquear por ahora.
- Crear migracion con indices basicos (RIT, RUC, fecha si aplica).
- Crear `TtyppRegistro` model + casts minimos.
- Conectar item `TTYPP` del navbar a ruta real del modulo.

### Criterios de aceptacion
- Existe tabla y modelo funcionales.
- Se puede navegar desde backoffice a pantalla TTYPP.
- No hay campos obligatorios a nivel validacion de app.

### Riesgos
- Ambiguedad en tipos de datos reales del Excel.
- Variantes de columnas por dia/turno no documentadas.

---

## Sprint 2 - Formulario CRUD (reemplazo operativo del Excel)
**Objetivo:** Tener formulario utilizable para carga diaria con todos los campos opcionales.

### Entregables
- Pantalla de listado tipo grilla (simil planilla).
- Formulario de alta/edicion con todos los campos `nullable`.
- Acciones: crear, editar, eliminar logico o fisico (segun decision tecnica).
- Busqueda simple por RIT/RUC/Acta/Sala.
- Integracion con `audiencias` para autocompletado de RUC por RIT.
- Integracion con catalogo de funcionarios para sugerencias por rol (TTyPP y Sala/Causa).

### Tareas
- Implementar componente Livewire/Volt para `index + form`.
- Validacion permisiva (`nullable`) y saneamiento basico (`trim`, normalizacion de espacios).
- Persistencia y mensajes UX (guardado exitoso/error).
- Tabla responsive para escritorio y mobile.
- Semilla de datos demo para QA.

### Criterios de aceptacion
- Usuario puede registrar una fila sin completar todos los campos.
- Usuario puede editar registros ya guardados.
- Vista lista permite revisar datos similares al Excel.
- RUC se completa automaticamente al ingresar un RIT existente en audiencias.
- Acta y encargadas cuentan con sugerencias por `datalist` desde catalogo de funcionarios.

### Riesgos
- Volumen alto de filas puede afectar rendimiento de grilla sin paginacion.

---

## Sprint 3 - Vista de avance en `welcome` (nueva pestana)
**Objetivo:** Mostrar progreso e indicadores TTYPP en `welcome`, en una pestana nueva, similar a Programacion de Audiencias.

### Entregables
- Nuevo modulo/micrositio en `welcome` llamado `TTyPP`, siguiendo el mismo layout visual de los modulos existentes.
- Vista de solo lectura del `Listado TTYPP` (mismas columnas clave que se usan en backoffice).
- Carga inicial filtrada por el dia en curso.
- Barra superior de navegacion por dias para cambiar fecha y visualizar datos almacenados de otros dias.
- Estado vacio cuando no existan registros para la fecha seleccionada.

### Tareas
- Integrar en `TurnoController@enviarTurno` los datos TTYPP necesarios para la vista publica en `welcome`.
- Implementar filtro por fecha con valor por defecto `hoy`.
- Construir barra de navegacion por dias (anterior/siguiente y fecha actual).
- Renderizar tabla TTYPP en modo lectura, con consistencia visual respecto al modulo `welcome`.
- Definir y documentar fuente de fecha para el filtro diario (fase inicial: `created_at`, salvo nuevo campo de fecha operativo).

### Criterios de aceptacion
- El modulo `TTyPP` aparece en `welcome` con el mismo lineamiento visual de los demas micrositios.
- Al abrir el modulo, se visualizan por defecto los registros del dia en curso.
- Usuario puede cambiar de dia desde la barra superior y ver la tabla correspondiente a esa fecha.
- La informacion mostrada coincide con los datos almacenados en `ttypp_registros` para el dia seleccionado.
- Si no hay datos en la fecha, se muestra mensaje de "sin registros" sin errores.

### Riesgos
- Dependencia de estructura actual de `welcome` (puede requerir refactor menor).
- Si no existe campo de fecha operativo en TTYPP, el filtro por dia dependera de `created_at` (fecha de carga).

---

## Sprint 4 - Automatizacion inicial y calidad de datos
**Objetivo:** Preparar el camino para automatizacion del flujo hoy manual en Excel.

### Entregables
- Catalogos base opcionales (tipo audiencia, modalidad, encargadas) sin volver obligatorios.
- Importador inicial Excel SIAGJ (`.xlsx` y `.xls`, modo asistido).
- Exportacion Excel `.xlsx` para continuidad operativa.
- Auditoria minima de cambios (quien crea/edita).

### Tareas
- Implementar pipeline de importacion con validaciones no bloqueantes.
- Resolver mapeo de columnas y tolerancia a archivos incompletos.
- Agregar trazabilidad de usuario y fecha de modificacion.
- Definir alertas basicas para registros incompletos criticos (solo advertencia).

### Criterios de aceptacion
- Se puede importar una planilla real con fallos menores sin romper proceso.
- Se puede exportar dataset filtrado.
- Cambios quedan auditables.

### Riesgos
- Variabilidad de formatos Excel historicos.
- Necesidad de reglas de negocio aun no formalizadas.

---

## Sprint 5 - Endurecimiento, permisos finos y salida a produccion
**Objetivo:** Cerrar el modulo para uso estable en produccion.

### Entregables
- Permisos granulares (`visible`, `editable`, `export`, `import`) para TTYPP.
- Pruebas Pest (Feature + componente) para flujo principal.
- Ajustes de rendimiento (paginacion, indices, eager loading si aplica).
- Documentacion funcional y runbook corto de operacion.

### Tareas
- Integrar permisos en navbar y endpoints.
- Escribir tests de:
  - Crear registro con campos `null`.
  - Editar/filtrar/listar.
  - Visibilidad de pestana TTYPP en `welcome`.
- Ejecutar QA con usuarios clave.
- Plan de despliegue por etapas (canary interno si aplica).

### Criterios de aceptacion
- Flujo principal estable y probado.
- Permisos aplicados correctamente.
- Modulo listo para reemplazar el Excel en operacion diaria.

---

## Definicion de Done (global)
- Codigo formateado y sin errores de estilo (`./vendor/bin/pint --test`).
- Pruebas relevantes pasando (`./vendor/bin/pest` o suite focalizada).
- Sin regresiones visibles en backoffice y `welcome`.
- Documentacion actualizada en `docs/implementaciones/`.

## Backlog tecnico recomendado
- Normalizacion progresiva de campos libres a catalogos.
- Versionado/historial de filas (bitacora por cambios de estado).
- Dashboard operativo con filtros por fecha, encargada y modalidad.
- Notificaciones automaticas (correo/alerta interna) para hitos del proceso.

## KPIs de adopcion sugeridos
- % de registros TTYPP cargados en sistema vs Excel.
- Tiempo promedio de carga por registro.
- % de registros incompletos (solo para mejora continua, no bloqueo).
- Errores operativos reportados por semana.
