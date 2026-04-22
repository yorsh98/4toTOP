# Implementacion Subir Informacion (Backoffice Documentos)

> Nota de implementacion acordada: se ejecuta sin crear nuevas tablas para procedimientos, documentos y migracion unijud. Se usa catalogo en JSON (`storage/app/backoffice/documentos_catalogo.json`) y se mantiene la tabla existente `buscadorsentencias` para la carga de Excel.

## 1) Objetivo

Centralizar en backoffice la carga y mantenimiento de informacion para estas vistas:

1. Migracion Unijud
2. Buscador de sentencias
3. Documentos
4. Procedimientos tribunal

El objetivo es eliminar datos hardcodeados en vistas y permitir gestion operativa por usuarios autorizados.

---

## 2) Estado actual (diagnostico)

- `resources/views/proc.blade.php` define un arreglo `$procedimientos` en la propia vista.
- `resources/views/doc.blade.php` define un arreglo `$doc` en la propia vista.
- `resources/views/migraunijud.blade.php` define un arreglo `$tribunales` en la propia vista.
- `resources/views/buscadorsentencias.blade.php` consume tabla `buscadorsentencias` via Livewire (`app/Livewire/BuscadorSentenciasTabla.php`).
- Existe importador de referencia en `database/seeders/BuscadorSentenciasSeeder.php`, que parsea el Excel `database/seeders/data/sentencias_para_el_buscador.xlsx`.

---

## 3) Alcance funcional del nuevo modulo Backoffice > Documentos

Crear un nuevo apartado en backoffice con 4 secciones (tabs o cards):

1. **Procedimientos tribunal**
2. **Documentos**
3. **Migracion Unijud**
4. **Buscador de sentencias (importacion Excel)**

### Reglas por seccion

#### 3.1 Procedimientos tribunal
- Formulario con 3 inputs:
  - `nombre_procedimiento` (texto, requerido)
  - `archivo` (file, requerido)
  - `orden` (numero, requerido, default incremental)
- Al guardar:
  - subir archivo
  - persistir metadata
  - reflejarlo en la vista publica que hoy usa `$procedimientos`

#### 3.2 Documentos
- Mismo esquema que Procedimientos (3 inputs):
  - `nombre_documento` (texto, requerido)
  - `archivo` (file, requerido)
  - `orden` (numero, requerido)
- Al guardar:
  - subir archivo
  - persistir metadata
  - reflejarlo en la vista publica que hoy usa `$doc`

#### 3.3 Migracion Unijud
- Mantener la lista que hoy alimenta `$tribunales` desde backoffice.
- Formulario propuesto (3 inputs):
  - `nombre_tribunal` (texto, requerido)
  - `orden` (numero, requerido)
  - `activo` (switch/checkbox, requerido)
- No requiere archivo para funcionar (solo poblar listado/array de tribunales).

#### 3.4 Buscador de sentencias
- Subida de 1 archivo Excel (`.xlsx`) desde backoffice.
- Leer el archivo y poblar la tabla `buscadorsentencias`.
- Formato esperado igual al ejemplo `database/seeders/data/sentencias_para_el_buscador.xlsx`.
- Mapeo de columnas (segun seeder actual):
  - A: RUC
  - B: RIT
  - C: ANO
  - D: NOMBRE_PARTES
  - E: MATERIA
  - F: FECHA_DECISION
  - G: GLOSA_DECISION
  - H: JUEZ
  - I: INSTANCIA

---

## 4) Diseno tecnico propuesto

### 4.1 Base de datos

#### Tabla `documentos_items`
Unifica Documentos + Procedimientos.

Campos sugeridos:
- `id`
- `tipo` enum: `procedimiento` | `documento`
- `nombre` string
- `archivo_path` string
- `archivo_nombre_original` string nullable
- `mime_type` string nullable
- `peso_bytes` unsignedBigInteger nullable
- `orden` unsignedInteger default 1
- `activo` boolean default true
- timestamps

Indices:
- (`tipo`, `activo`)
- (`tipo`, `orden`)

#### Tabla `migracion_unijud_tribunales`
Campos sugeridos:
- `id`
- `nombre` string
- `orden` unsignedInteger default 1
- `activo` boolean default true
- timestamps

Indices:
- (`activo`, `orden`)

#### Tabla `buscadorsentencias_imports` (opcional pero recomendada)
Auditoria de cargas.

Campos:
- `id`
- `archivo_nombre` string
- `archivo_path` string nullable
- `total_filas` int default 0
- `insertadas` int default 0
- `omitidas` int default 0
- `estado` enum: `ok` | `error`
- `errores` longText nullable
- `user_id` foreignId nullable
- timestamps

---

### 4.2 Backoffice (UI + permisos)

#### Rutas
Crear grupo autenticado:
- `GET /backoffice/documentos` (pantalla principal)
- Acciones Livewire para crear/editar/eliminar/importar

#### Componente
Crear pagina Livewire (ejemplo):
- `App\Livewire\DocumentosBackofficePage`
- Vista: `resources/views/livewire/documentos-backoffice-page.blade.php`

#### Permisos
Agregar permisos nuevos:
- `navbar.documentos.visible`
- `navbar.documentos.editable`
- `documentos.ver`
- `documentos.gestionar`
- `sentencias.importar`

Incluirlos en:
- `database/seeders/RolesAndPermissionsSeeder.php`
- bootstrap RBAC: `database/migrations/2026_03_31_160000_bootstrap_rbac_catalog.php`
- menu: `resources/views/livewire/layout/navigation.blade.php`

---

### 4.3 Almacenamiento de archivos

Usar filesystem de Laravel en disco `public` (`storage/app/public`) y exponer via `/storage`.
Rutas sugeridas:
- `documentos/procedimientos/...`
- `documentos/doc/...`

Notas:
- normalizar nombres de archivo (slug + timestamp)
- guardar nombre original para mostrar en backoffice
- validar mime y tamano

---

### 4.4 Integracion con vistas actuales

Reemplazar arreglos hardcodeados por datos dinamicos:

- `proc.blade.php`: obtener items tipo `procedimiento`, activos, ordenados por `orden`.
- `doc.blade.php`: obtener items tipo `documento`, activos, ordenados por `orden`.
- `migraunijud.blade.php`: obtener tribunales activos, ordenados por `orden`.

Implementacion preferida:
- mover carga de datos a controlador/servicio (evitar logica en blade).

---

### 4.5 Importacion Excel de sentencias

Reutilizar criterio de `BuscadorSentenciasSeeder`:
- detectar encabezado por celda A == `RUC` en primeras filas
- parseo robusto de fecha (DateTime, serial Excel, string)
- insercion en lotes (batch)

Flujo recomendado de import:
1. validar archivo `.xlsx`
2. parsear y validar estructura
3. ejecutar en transaccion
4. estrategia de carga:
   - opcion A (recomendada): truncar + recargar completo
   - opcion B: upsert por llave logica (si se define llave unica)
5. registrar resultado en `buscadorsentencias_imports`
6. mostrar resumen al usuario

---

## 5) Validaciones y reglas de negocio

- Solo usuarios con `documentos.gestionar` pueden cargar/editar.
- Validacion archivos:
  - Procedimientos/Documentos: `pdf,doc,docx` (tamano configurable)
  - Buscador sentencias: solo `xlsx`
- `orden` debe ser entero >= 1.
- `nombre` obligatorio y longitud maxima (ej. 255).
- En eliminacion, preferir `activo=false` (baja logica) para trazabilidad.

---

## 6) Plan de implementacion por fases

### Fase 1 - Estructura base
- Migraciones nuevas (`documentos_items`, `migracion_unijud_tribunales`, opcional `buscadorsentencias_imports`)
- Modelos Eloquent
- Seed inicial opcional para migrar data hardcodeada existente

### Fase 2 - Backoffice Documentos
- Ruta protegida
- Componente Livewire con 4 tabs
- Formularios y listados CRUD
- Carga de archivos con `WithFileUploads`

### Fase 3 - Integracion vistas publicas/operativas
- `proc`, `doc`, `migraunijud` consumiendo BD
- quitar arreglos hardcodeados

### Fase 4 - Importador de sentencias
- Implementar servicio importador
- Integrar carga Excel desde backoffice
- Registrar auditoria y mostrar resultado

### Fase 5 - Seguridad + QA
- Permisos/RBAC en seeders y menu
- Pruebas Feature/Livewire/Pest
- validacion final funcional con datos reales

---

## 7) Pruebas sugeridas (Pest)

- Acceso:
  - usuario sin permiso no entra a backoffice documentos
  - usuario con permiso si entra
- Procedimientos/documentos:
  - crea item con archivo valido
  - rechaza archivo invalido
  - respeta orden
- Unijud:
  - crea/edita/inactiva tribunal
  - lista publica refleja activos en orden
- Sentencias:
  - importa excel valido y puebla `buscadorsentencias`
  - rechaza excel sin encabezado esperado
  - registra resultado del import

---

## 8) Riesgos y mitigaciones

- **Riesgo:** romper enlaces existentes por cambio de ubicacion archivos.
  - **Mitigacion:** mantener estructura de paths compatible o generar redirecciones.
- **Riesgo:** importaciones pesadas de Excel.
  - **Mitigacion:** batch inserts, limites y mensaje de progreso/resultado.
- **Riesgo:** datos duplicados o inconsistentes en sentencias.
  - **Mitigacion:** definir estrategia unica (truncar+recargar o upsert).

---

## 9) Criterios de aceptacion

- Existe menu backoffice para gestionar los 4 apartados.
- Procedimientos y Documentos permiten alta via formulario de 3 inputs y se reflejan en vistas.
- Migracion Unijud se mantiene desde backoffice sin editar blade.
- Buscador de sentencias se actualiza cargando Excel desde backoffice.
- Todo protegido por permisos y con trazabilidad basica de cargas.

---

## 10) Entregables tecnicos

- Nuevas migraciones + modelos + componente Livewire.
- Nuevas rutas backoffice y permisos.
- Actualizacion de vistas `proc`, `doc`, `migraunijud` para consumir DB.
- Servicio/importador de sentencias desde Excel.
- Pruebas automatizadas Pest para flujos criticos.
