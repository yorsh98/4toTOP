# Plan de Integracion: Modulo de Insumos

## Objetivo

Implementar un modulo independiente para administrar insumos consumibles, solicitudes, entregas, stock por bodega, reposiciones, importaciones desde Excel, notificaciones y reportes de asignacion por persona.

El modulo mantendra trazabilidad completa de cada cambio de stock y reutilizara la arquitectura existente del proyecto: Laravel 13, Livewire 3, Tailwind CSS, Pest, Spatie Permissions, NotificationsV2, PhpSpreadsheet y los sectores del mapa de Servicios.

## Decisiones cerradas

- El modulo se llamara **Insumos** y tendra navegacion propia.
- Las rutas estaran bajo `/insumos`; no dependeran de `/backoffice/servicios`.
- La tabla `funcionarios`, su catalogo y `config/funcionarios.php` quedan fuera de alcance.
- Solicitantes, personas asignadas y destinatarios de correo se obtendran desde `users`.
- El rol Spatie existente se llama exactamente `servicios`, en minuscula.
- No se creara, renombrara ni eliminara el rol `servicios`.
- Los permisos del modulo podran asignarse a cualquier rol desde Seguridad.
- El rol no controlara por si solo el acceso: las rutas y acciones verificaran permisos.
- Las notificaciones operativas a la Unidad de Servicios se enviaran a usuarios habilitados con rol `servicios`.
- Servicios recibira correo y notificacion en la bandeja interna.
- La persona que recibe una asignacion recibira un correo en `users.email`.
- Una solicitud podra incluir varios insumos.
- Una solicitud podra recibir entregas parciales.
- El stock se descontara solamente al registrar una asignacion o entrega.
- El stock minimo inicial sera 15 por insumo y podra editarse.
- El mapa mostrara el stock fisico por sector o bodega.
- Los archivos Excel futuros representaran el saldo total vigente, no una compra que deba sumarse.

## Estado actual reutilizable

- El modelo `Sector` y la tabla `sectors` ya representan las ubicaciones del mapa.
- Las bodegas 807, 811, 818 y 820 ya existen como sectores del piso 8.
- `ServiciosMapaPage` ya resuelve hotspots mediante `sectors.identificador_mapa`.
- `SafeSpreadsheetReader` ya valida y lee archivos XLS/XLSX con PhpSpreadsheet.
- NotificationsV2 ya ofrece plantillas, destinatarios, cola, trazabilidad e idempotencia para correos.
- `User` ya usa `HasRoles` de Spatie y `Notifiable` de Laravel.
- La pantalla de Seguridad ya permite asignar permisos a cualquier rol.

## Fuera de alcance

- Modificar el catalogo global de funcionarios.
- Relacionar `users` con `funcionarios`.
- Crear otro rol llamado Servicios.
- Convertir los activos fijos existentes en insumos.
- Exponer solicitudes, asignaciones o stock en el mapa publico.
- Incorporar costos, PPP o valorizacion contable durante el MVP.
- Implementar notificaciones en tiempo real con WebSockets.

## Modulo y rutas

### Navegacion principal

Agregar un elemento independiente **Insumos** en el menu autenticado. No se ubicara dentro de Servicios ni reutilizara sus permisos.

### Rutas propuestas

| Ruta | Proposito | Permiso |
|---|---|---|
| `/insumos/solicitudes` | Crear solicitudes y consultar las propias | `insumos.solicitar` |
| `/insumos/gestion` | Gestionar solicitudes y asignaciones directas | `insumos.gestionar` |
| `/insumos/inventario` | Administrar catalogo, stock y reposiciones | `insumos.inventario` |
| `/insumos/importaciones` | Previsualizar y confirmar archivos Excel | `insumos.importar` |
| `/insumos/reportes` | Consultar asignaciones por persona e historial | `insumos.reportes` |

Todas las rutas requeriran autenticacion. Cada componente volvera a autorizar sus acciones sensibles para no depender solamente del middleware o de botones ocultos.

## Permisos

Crear solamente permisos, sin crear ni sincronizar roles automaticamente:

- `navbar.insumos.visible`
- `navbar.insumos.editable`
- `insumos.solicitar`
- `insumos.gestionar`
- `insumos.inventario`
- `insumos.importar`
- `insumos.reportes`

La pantalla de Seguridad permitira asignarlos al rol `servicios` o a cualquier otro rol. La seleccion de destinatarios para avisos operativos seguira siendo exclusivamente `User::role('servicios')`, independientemente de que otros roles tengan permisos administrativos.

## Modelo de datos

### Tabla `insumos`

Catalogo principal.

- `id`
- `codigo` string, unico
- `nombre`
- `unidad_medida`
- `stock_minimo` unsigned integer, default 15
- `activo` boolean, default true
- timestamps

El codigo se almacenara como texto para conservar ceros iniciales y evitar notacion cientifica al importar desde Excel.

### Tabla `insumo_existencias`

Saldo actual por insumo y bodega.

- `id`
- `insumo_id` foreign key
- `sector_id` foreign key
- `cantidad` unsigned integer
- timestamps
- unique compuesto: `insumo_id`, `sector_id`

La cantidad no se editara directamente desde la interfaz. Todo cambio debera originar un movimiento.

### Tabla `insumo_solicitudes`

Cabecera de una solicitud.

- `id`
- `solicitante_user_id` foreign key a `users`
- `estado`: `pendiente`, `parcial`, `completada`, `rechazada`, `cancelada`
- `observacion` nullable
- `rechazada_por_user_id` nullable
- `motivo_rechazo` nullable
- timestamps

### Tabla `insumo_solicitud_detalles`

- `id`
- `insumo_solicitud_id` foreign key
- `insumo_id` foreign key
- `cantidad_solicitada` unsigned integer
- timestamps
- unique compuesto: `insumo_solicitud_id`, `insumo_id`

La cantidad entregada se calculara sumando los movimientos de asignacion asociados. Esto permite entregas parciales sin duplicar el saldo en la solicitud.

### Tabla `insumo_movimientos`

Historial inmutable y fuente de auditoria del stock.

- `id`
- `operacion_uuid`, para agrupar varias lineas de una misma entrega o importacion
- `insumo_id` foreign key
- `sector_id` foreign key
- `tipo`: `entrada`, `asignacion`, `ajuste_importacion`
- `cantidad` unsigned integer
- `stock_anterior` unsigned integer
- `stock_posterior` unsigned integer
- `asignado_a_user_id` nullable, foreign key a `users`
- `solicitud_detalle_id` nullable
- `importacion_id` nullable
- `created_by_user_id` foreign key a `users`
- `observacion` nullable
- timestamps

Las asignaciones directas tendran `asignado_a_user_id` y no tendran `solicitud_detalle_id`. Las entregas solicitadas tendran ambos campos.

### Tabla `insumo_importaciones`

Auditoria de cada archivo procesado.

- `id`
- `archivo_nombre`
- `archivo_hash` SHA-256, unico
- `fecha_stock` nullable
- `imported_by_user_id` foreign key a `users`
- `estado`: `procesando`, `completada`, `fallida`
- `filas_leidas`
- `insumos_creados`
- `saldos_actualizados`
- `sin_cambios`
- `advertencias` JSON nullable
- `error` nullable
- timestamps

El hash impedira aplicar dos veces el mismo archivo.

### Tabla `notifications`

Usar la tabla estandar de notificaciones de Laravel para la bandeja interna:

- identificador UUID
- tipo de notificacion
- usuario destinatario
- payload JSON
- `read_at`
- timestamps

No se duplicara la infraestructura de lectura/no lectura que Laravel ya proporciona mediante `Notifiable`.

## Reglas de personas y destinatarios

### Personas solicitantes

- La persona autenticada sera el solicitante.
- La solicitud guardara `Auth::id()`.
- No se consultara ni modificara `funcionarios`.
- Solo usuarios aprobados, no rechazados y no suspendidos podran operar normalmente en el sistema.

### Personas asignables

- Las asignaciones se realizaran a registros de `users`.
- Se mostraran cuentas aprobadas, no rechazadas y no suspendidas.
- El correo de asignacion se enviara a `users.email`.
- El reporte por persona agrupara por `asignado_a_user_id`.

### Unidad de Servicios

Los destinatarios se resolveran con el rol de Seguridad existente:

```php
User::role('servicios')
    ->whereNotNull('approved_at')
    ->whereNull('rejected_at')
    ->whereNull('suspended_at');
```

Tambien se filtraran correos invalidos y se deduplicaran destinatarios antes de crear el despacho.

## Flujo de solicitudes

1. El usuario abre `/insumos/solicitudes`.
2. Busca insumos activos y revisa disponibilidad total.
3. Agrega uno o varios insumos con cantidades mayores a cero.
4. Confirma la solicitud.
5. Se crea la cabecera y sus detalles en una transaccion.
6. El stock no cambia en esta etapa.
7. Se notifica a los usuarios con rol `servicios` por correo y bandeja.
8. El solicitante puede consultar estado, cantidades solicitadas y cantidades entregadas.

No se reservara stock al solicitar. La disponibilidad se validara nuevamente al entregar.

## Flujo de entrega solicitada

1. Un usuario con `insumos.gestionar` abre una solicitud pendiente o parcial.
2. Selecciona las cantidades a entregar y la bodega de origen.
3. El sistema bloquea cada existencia con `lockForUpdate()` dentro de una transaccion.
4. Se verifica que exista stock suficiente y que la entrega no supere la cantidad pendiente.
5. Se crea un movimiento `asignacion` por cada insumo.
6. Se actualizan las existencias.
7. La solicitud queda `parcial` o `completada` segun sus cantidades pendientes.
8. Se registra la notificacion antes de finalizar la transaccion.
9. Los jobs de correo se despachan despues del commit.

Si falla cualquier linea, se revierte la entrega completa para no dejar una operacion parcial accidental.

## Flujo de asignacion directa

1. Un usuario con `insumos.gestionar` selecciona una persona de `users`.
2. Agrega uno o varios insumos y selecciona sus bodegas de origen.
3. El sistema aplica el mismo bloqueo, validacion, movimiento y descuento del flujo solicitado.
4. Los movimientos no se relacionan con una solicitud.
5. Servicios recibe correo y bandeja por la asignacion.
6. La persona asignada recibe un correo con el resumen de lo entregado.

## Reposicion manual

1. Un usuario con `insumos.inventario` selecciona insumo, sector y cantidad recibida.
2. La cantidad debe ser mayor a cero.
3. Se bloquea o crea la fila de existencia.
4. Se registra un movimiento `entrada` con saldo anterior y posterior.
5. Se actualiza el saldo dentro de la misma transaccion.

No existira una accion para sobrescribir manualmente el saldo sin movimiento.

## Stock minimo

- Cada insumo comenzara con `stock_minimo = 15`.
- El umbral se comparara contra el stock total del insumo sumando todos sus sectores.
- Se alertara cuando el total pase de estar sobre el minimo a quedar igual o bajo el minimo.
- Permanecer bajo el minimo no generara un correo por cada nueva consulta o guardado.
- Una reposicion sobre el minimo rearmara la alerta para un cruce futuro.
- Llegar a cero se presentara visualmente como agotado.
- Una entrega que deje varios insumos bajos generara un resumen, no un correo independiente por cada fila.

La evaluacion se ejecutara dentro de la transaccion de asignacion. El envio quedara programado despues del commit.

## Notificaciones

### Matriz funcional

| Evento | Disparador | Servicios | Persona asignada |
|---|---|---|---|
| `insumo.solicitado` | Creacion exitosa de solicitud | Correo + bandeja | No aplica |
| `insumo.asignado` | Entrega solicitada o directa | Correo + bandeja | Correo |
| `insumo.stock_bajo` | Cruce de stock total a minimo o menos | Correo + bandeja | No aplica |

### Correo

- Reutilizar NotificationsV2.
- Crear tipos y plantillas idempotentes para los tres eventos.
- Crear un notifier de Insumos que use el orquestador existente.
- Usar destinatarios manuales obtenidos desde `User::role('servicios')`.
- Enviar un mensaje individual por destinatario para no revelar correos.
- Usar claves de idempotencia basadas en solicitud u operacion persistida.
- Despachar siempre con `afterCommit()`.

### Bandeja interna

- Usar notificaciones `database` nativas de Laravel.
- Agregar campana en la navegacion autenticada.
- Mostrar contador de no leidas.
- Crear una pagina o panel con historial cronologico.
- Permitir marcar una notificacion o todas como leidas.
- Enlazar cada aviso con la solicitud o pantalla relacionada.
- Actualizar al navegar o refrescar; no usar WebSockets en el MVP.

## Importacion desde Excel

### Archivo base analizado

Archivo: `/home/jorge/Descargas/StockBodegaUbicacion 11.09.xls`

- Formato real XLS binario.
- Una hoja: `StockBodegaUbicacion (89)`.
- Encabezado en fila 12.
- 169 registros de insumos.
- 169 codigos unicos.
- Stock total informado: 9.068 unidades.
- Bodegas fisicas: 807, 811, 818 y 820.
- Unidades encontradas: `UN`, `CJ`, `RE`, `RO` y `PQ`.
- Una fila tiene stock cero.
- Un codigo tiene 11 digitos y se conservara sin alterarlo.
- Las fechas `"/ /"` se normalizaran como nulas.

### Columnas utilizadas

| Excel | Destino |
|---|---|
| `Codigo` | `insumos.codigo` |
| `Nombre Articulo` | `insumos.nombre` |
| `UME` | `insumos.unidad_medida` |
| `Stock Actual` | `insumo_existencias.cantidad` |
| `UBICACION` | Sector mediante `identificador_mapa` |

Las columnas PPP, Valor Total, lote, serie, rotable, Stock UMA y ubicacion ERP no se persistiran en el MVP porque no participan en el control solicitado.

### Validacion y previsualizacion

1. Aceptar solamente `.xls` y `.xlsx` con MIME y contenido validos.
2. Limitar tamano, filas y columnas.
3. Leer mediante `SafeSpreadsheetReader` con soporte XLS habilitado.
4. Detectar la fila que contenga simultaneamente `Bodega`, `Codigo` y `Nombre Articulo`.
5. Validar todas las filas antes de iniciar escrituras.
6. Informar codigos duplicados, cantidades invalidas y ubicaciones desconocidas.
7. Mostrar resumen y advertencias antes de confirmar.
8. Bloquear la confirmacion si existe un error que impida sincronizar el reporte completo.

### Sincronizacion

- La importacion representa el saldo total vigente.
- El catalogo se actualizara por `codigo`.
- La ubicacion `BODEGA 807` se resolvera como `sectors.identificador_mapa = 807`, y lo mismo para las demas.
- Para cada diferencia se creara un movimiento `ajuste_importacion`.
- El saldo actual quedara igual al informado por el archivo.
- Reimportar el mismo hash no producira cambios.
- Los saldos ausentes se ajustaran a cero solamente cuando el archivo sea un reporte completo de todas las bodegas.
- El archivo original se conservara en almacenamiento privado para auditoria.
- El resultado mostrara creados, actualizados, sin cambios, ajustados a cero y advertencias.

La importacion completa se ejecutara en una transaccion. Una fila invalida no dejara una sincronizacion parcial.

## Integracion con el mapa

Ampliar el mapa privado de Servicios sin mezclar insumos con `Activo`:

- Mantener los conteos actuales de activos.
- Agregar `stock_insumos_count` por sector.
- Mostrar el total de unidades almacenadas en cada hotspot.
- En el detalle mostrar codigo, nombre, unidad y cantidad por insumo.
- Permitir buscar por codigo o nombre de insumo.
- Mantener solicitudes, asignaciones y stock fuera del mapa publico.

## Pantallas Livewire

### Solicitudes

- Catalogo buscable y paginado.
- Disponibilidad total por insumo.
- Carrito con cantidades.
- Confirmacion de solicitud.
- Historial propio.
- Detalle de solicitado, entregado y pendiente.

### Gestion

- Resumen de pendientes.
- Filtros por solicitante, estado y fecha.
- Detalle de solicitud.
- Entrega parcial o completa.
- Rechazo y cancelacion con motivo.
- Asignacion directa a un usuario.

### Inventario

- CRUD del catalogo.
- Stock por bodega.
- Indicadores normal, bajo y agotado.
- Reposicion manual.
- Historial de movimientos.
- Desactivacion de insumos con historial en lugar de borrado fisico.

### Reportes

- Totales asignados por persona.
- Detalle por insumo, fecha y bodega.
- Filtros por persona y rango de fechas.
- Totales solicitados, entregados y pendientes.
- Exportacion puede quedar para una fase posterior si no es necesaria para operar el MVP.

## Plan de implementacion

## Sprint 1 - Base de datos, modelos y permisos

### Tareas

1. Crear las migraciones de Insumos y la tabla de notificaciones de Laravel.
2. Crear modelos y relaciones Eloquent.
3. Crear los permisos sin modificar roles existentes.
4. Integrar el grupo Insumos en la pantalla de permisos.
5. Agregar las rutas y el elemento independiente de navegacion.
6. Crear factories minimas para pruebas.

### Criterio de salida

El esquema migra correctamente y cada ruta queda protegida por su permiso.

## Sprint 2 - Servicio transaccional de stock

### Tareas

1. Implementar entradas y asignaciones mediante un unico flujo de movimientos.
2. Aplicar `DB::transaction()` y `lockForUpdate()`.
3. Impedir stock negativo.
4. Agrupar operaciones de varios insumos con `operacion_uuid`.
5. Calcular cruces del stock minimo.
6. Mantener movimientos inmutables.

### Criterio de salida

Toda alteracion de stock produce un movimiento consistente y una operacion fallida no deja cambios parciales.

## Sprint 3 - Importacion inicial y reposiciones

### Tareas

1. Implementar parser y validacion del formato externo.
2. Crear previsualizacion de importacion.
3. Implementar sincronizacion idempotente por hash.
4. Importar el archivo base de 169 registros.
5. Validar los saldos de las cuatro bodegas.
6. Implementar reposicion manual.

### Criterio de salida

El archivo base puede cargarse una vez, reimportarse sin duplicar stock y conciliar 9.068 unidades.

## Sprint 4 - Solicitudes

### Tareas

1. Crear catalogo de solicitud y carrito.
2. Guardar solicitudes con varios detalles.
3. Mostrar historial propio.
4. Calcular cantidades entregadas y pendientes.
5. Implementar cancelacion segun las reglas de estado.

### Criterio de salida

Un usuario autorizado puede solicitar varios insumos sin descontar stock y consultar el avance de su solicitud.

## Sprint 5 - Gestion y asignaciones

### Tareas

1. Crear listado de solicitudes pendientes y parciales.
2. Implementar entregas parciales y completas.
3. Implementar asignacion directa.
4. Implementar rechazo con motivo.
5. Mostrar disponibilidad por bodega durante la entrega.
6. Actualizar estados de solicitud de forma atomica.

### Criterio de salida

Un usuario con permisos administrativos puede entregar o asignar insumos sin generar saldos negativos.

## Sprint 6 - Correo y bandeja interna

### Tareas

1. Crear plantillas para solicitud, asignacion y stock bajo.
2. Resolver Servicios mediante `User::role('servicios')`.
3. Integrar los despachos con NotificationsV2.
4. Crear notificaciones de base de datos para Servicios.
5. Agregar campana, contador y marcado como leido.
6. Verificar `afterCommit()` e idempotencia.

### Criterio de salida

Cada evento genera exactamente los avisos definidos sin enviar a cuentas inhabilitadas ni duplicar despachos.

## Sprint 7 - Reportes y mapa

### Tareas

1. Crear reporte de asignaciones por usuario.
2. Agregar filtros por fecha, persona e insumo.
3. Incorporar stock por sector al mapa privado.
4. Mantener aislado el mapa publico.
5. Revisar consultas y evitar N+1.

### Criterio de salida

Se puede auditar lo entregado a cada persona y consultar el stock fisico desde el mapa privado.

## Sprint 8 - QA y despliegue

### Tareas

1. Ejecutar pruebas funcionales y de autorizacion.
2. Ejecutar `./vendor/bin/pint --test`.
3. Verificar migraciones en una copia de produccion.
4. Confirmar que el rol `servicios` existe antes del despliegue.
5. Asignar los nuevos permisos desde Seguridad.
6. Confirmar que las cuentas Servicios estan aprobadas y tienen correo valido.
7. Confirmar que NotificationsV2 y el worker de cola estan habilitados.
8. Importar el Excel base y conciliar totales.
9. Validar rollback y respaldo.

### Criterio de salida

El modulo queda operativo en produccion con stock inicial conciliado, accesos configurados y notificaciones procesadas por cola.

## Pruebas minimas

### Autorizacion

- Un usuario sin `navbar.insumos.visible` no ve el menu.
- Un usuario sin el permiso de una ruta recibe 403.
- Cualquier rol puede operar si recibe el permiso correspondiente.
- El rol `servicios` no se crea ni se renombra durante migraciones o seeders.

### Solicitudes

- Una solicitud admite varios insumos.
- No admite cantidades cero o negativas.
- Crear una solicitud no descuenta stock.
- Un usuario solo consulta sus propias solicitudes, salvo que tenga `insumos.gestionar`.
- Las entregas parciales actualizan correctamente el estado.

### Stock

- Una entrada incrementa saldo y crea movimiento.
- Una asignacion descuenta saldo y crea movimiento.
- No se puede asignar mas que el stock disponible.
- Una operacion de varias lineas hace rollback completo si una linea falla.
- Dos entregas concurrentes no producen stock negativo.

### Importacion

- El archivo XLS real es aceptado.
- Se detecta el encabezado en la fila 12.
- Los codigos se guardan como texto.
- Las ubicaciones se vinculan con los sectores correctos.
- El total inicial concilia 9.068 unidades.
- Reimportar el mismo archivo no altera saldos.
- Una importacion invalida no deja cambios parciales.

### Notificaciones

- Una solicitud avisa a todos y solo los usuarios habilitados con rol `servicios`.
- Una asignacion avisa a Servicios y envia correo a la persona asignada.
- El cruce del minimo genera una sola alerta.
- Permanecer bajo el minimo no repite la alerta.
- Reponer sobre el minimo permite una alerta futura.
- Un rollback no deja correo ni notificacion interna pendiente.
- La bandeja permite marcar avisos como leidos.

### Reportes y mapa

- Los totales por persona se obtienen desde movimientos de asignacion.
- Los filtros no mezclan usuarios ni periodos.
- Cada hotspot privado muestra el stock correcto del sector.
- El mapa publico no expone stock ni asignaciones.

## Riesgos y controles

| Riesgo | Control |
|---|---|
| Descuento concurrente | Transaccion y `lockForUpdate()` |
| Stock negativo | Validacion dentro del bloqueo y columna unsigned |
| Excel aplicado dos veces | Hash SHA-256 unico |
| Reporte parcial interpretado como completo | Validacion del alcance antes de ajustar ausentes a cero |
| Correo antes del commit | Jobs con `afterCommit()` |
| Correos repetidos | Llaves idempotentes por solicitud u operacion |
| Acceso por interfaz sin autorizacion real | Middleware y autorizacion en acciones Livewire |
| Destinatarios obsoletos | Filtrar usuarios aprobados, no rechazados y no suspendidos |
| Perdida de auditoria | Movimientos inmutables y archivo original privado |

## Orden de ejecucion recomendado

1. Base de datos y permisos.
2. Servicio transaccional de stock.
3. Importacion y carga inicial.
4. Solicitudes.
5. Gestion y asignaciones.
6. Notificaciones y bandeja.
7. Reportes y mapa.
8. QA y despliegue.

## Definicion de terminado

- El modulo Insumos aparece como seccion independiente.
- Los accesos dependen de permisos asignables a cualquier rol.
- El rol existente `servicios` recibe los avisos operativos sin ser modificado por la implementacion.
- No se modifica el sistema de Funcionarios.
- El Excel base queda importado y conciliado.
- Cada cambio de stock tiene un movimiento auditable.
- Las solicitudes y asignaciones actualizan sus estados correctamente.
- No es posible producir stock negativo.
- Los correos y la bandeja respetan destinatarios, commit e idempotencia.
- El reporte muestra lo asignado a cada usuario.
- El mapa privado muestra existencias por bodega.
- Las pruebas relevantes y Pint finalizan correctamente.
