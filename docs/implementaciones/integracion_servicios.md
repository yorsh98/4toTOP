# Plan de Integración: Módulo de Servicios e Inventario Interactivo

## Visión General
Este documento detalla la planificación mediante metodología Scrum para la implementación del nuevo subsistema de la **Unidad de Servicios del Tribunal**. El objetivo es crear un inventario interactivo que permita tanto la administración interna (Backoffice) como la consulta pública (Vista Pública) a través de un mapa o plano de las dependencias.

Se mantendrá la línea arquitectónica del proyecto: **Laravel 13**, **Livewire/Volt**, **Tailwind CSS**, pruebas con **Pest** y control de accesos con **Spatie Permissions**.

---

## Sprint 1: Arquitectura de Datos y Modelado Base
**Objetivo:** Diseñar la estructura de la base de datos, configurar los modelos y poblar datos iniciales para soportar el inventario y las ubicaciones físicas.

**Historias de Usuario / Tareas:**
1. **Modelo `Sector` (o Ubicación):**
   - Crear migración y modelo para representar los puntos del mapa (hotspots).
   - Campos clave: `nombre`, `piso`, `identificador_mapa` (para linkear con la maqueta HTML).
2. **Modelo `Activo` (o Bien/Insumo):**
   - Crear migración y modelo para los bienes del inventario.
   - Campos clave: `nombre`, `sector_id` (relación), `numero_serie` o `codigo_inventario`, `estado` (operativo, en reparación, dado de baja), `observaciones`.
3. **Roles y Permisos (Spatie):**
   - Crear el rol `servicios` o `inventario`.
   - Crear permisos granulares: `servicios.ver`, `servicios.crear`, `servicios.editar`, `servicios.eliminar`.
4. **Seeders y Factories:**
   - Escribir `SectorSeeder` para precargar las zonas del Piso 8 basándose en la maqueta proporcionada.
   - Configurar factories para realizar pruebas rápidamente.

---

## Sprint 2: Backoffice y Gestión de Inventario
**Objetivo:** Proveer a los funcionarios autorizados de una interfaz administrativa (CRUD) para mantener el inventario al día.

**Historias de Usuario / Tareas:**
1. **Componentes Livewire para Gestión:**
   - Crear la página de listado en el backoffice usando Livewire (e.g. `ServiciosInventarioPage`).
   - Implementar un formulario para creación y edición de bienes, asignándoles el `Sector` correspondiente.
2. **Filtros y Búsqueda:**
   - Implementar barra de búsqueda por número de inventario, nombre o sector.
   - Paginación dinámica y filtrado por estado del activo.
3. **Validación y Seguridad:**
   - Proteger el acceso a esta ruta usando el middleware de Spatie para validar que el usuario tenga el rol/permiso correspondiente.
   - Implementar Form Requests / validación Livewire estricta.
4. **Pruebas (Pest):**
   - Crear el archivo `ServiciosInventarioTest.php`.
   - Testear que un usuario sin permisos reciba un 403 Forbidden.
   - Testear la creación, edición y visualización de un activo en la base de datos.

---

## Sprint 3: Integración de la Vista Interactiva (Mapa) en el Backoffice
**Objetivo:** Traducir la maqueta HTML estática a un componente interactivo de Livewire/Alpine.js para el equipo interno.

**Historias de Usuario / Tareas:**
1. **Adaptación de la Maqueta (UI/UX):**
   - Convertir `plano-insumos.html` en una vista Blade / Livewire.
   - Mover el CSS de la maqueta a clases nativas de Tailwind CSS, respetando la estructura y el esquema de colores de la aplicación actual.
2. **Interactividad Dinámica (Alpine.js + Livewire):**
   - Hacer que los puntos del mapa (hotspots) reaccionen al pasar el ratón o hacer click mediante Alpine.js o acciones directas de Livewire.
   - Cargar dinámicamente desde el backend el contador (badge) de cada sector con la cantidad de activos asignados.
3. **Modal de Detalle por Sector:**
   - Al hacer click en un hotspot, disparar un evento que abra el modal y cargue la lista de activos pertenecientes a ese sector.
4. **Pruebas (Pest):**
   - Testear que la vista principal del mapa renderice correctamente.
   - Testear que la petición de activos por sector devuelva los datos correctos en formato JSON/HTML.

---

## Sprint 4: Vista Pública e Integración Final
**Objetivo:** Habilitar una versión de sólo lectura del mapa interactivo accesible para el público general sin necesidad de autenticación, y pulir detalles finales.

**Historias de Usuario / Tareas:**
1. **Ruta Pública (Controller/Livewire):**
   - Crear una ruta accesible sin middleware de autenticación (ej: `/servicios/mapa-publico`).
   - Reutilizar el componente del mapa del Sprint 3, pero inyectando un parámetro que deshabilite cualquier botón de edición o acceso al Backoffice.
2. **Optimización de Consultas:**
   - Aplicar Eager Loading (`with('activos')`) en la carga del mapa para evitar el problema N+1 al calcular los totales por sector.
3. **Ajustes de Diseño:**
   - Asegurarse de que el mapa sea completamente funcional y *responsive* (adaptable a resoluciones de pantallas pequeñas y móviles).
4. **Pruebas (Pest) y Cierre:**
   - Testear el acceso público a la ruta (HTTP 200 OK para invitados).
   - Validar que un usuario invitado no pueda ejecutar mutaciones ni acceder a datos sensibles.

---

## Consideraciones Técnicas
- **Imágenes y Planos:** La imagen base del mapa deberá almacenarse en `public/images/planos/` y no en base64 en línea, para aprovechar el caché del navegador.
- **Micro-interacciones:** Se sugiere usar Alpine.js (`x-data`, `x-show`, `x-on:click`) para manejar el modal de visualización en el cliente de forma rápida, consultando a Livewire (`wire:click`) solo para obtener los datos específicos del sector.
