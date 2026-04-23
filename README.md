# Sistema de Oficios y Libertades

Aplicación web del **4to Tribunal Oral en lo Penal de Santiago** para administrar el ciclo de oficios, libertades y procesos administrativos relacionados.

## Resumen del sistema

El sistema centraliza la operación diaria en una sola plataforma y contempla:

- Gestión de solicitudes y seguimiento de estado.
- Módulos funcionales organizados por rutas/controladores.
- Control de acceso por autenticación, roles y permisos.
- Tareas en segundo plano para procesos asíncronos.

## Arquitectura del sistema

La aplicación implementa una arquitectura monolítica basada en Laravel, separada por capas para facilitar mantenimiento y escalabilidad funcional.

### 1) Presentación (Frontend/UI)

- Plantillas Blade como base de renderizado.
- Componentes reactivos con **Livewire 3** y **Volt**.
- Estilos con **Tailwind CSS 3** y **Bootstrap 5**.
- Gestión de assets y build con **Vite 6**.

### 2) Aplicación (HTTP + negocio)

- Endpoints y navegación definidos en `routes/web.php`.
- Casos de uso implementados en controladores de `app/Http/Controllers`.
- Validaciones y reglas de negocio con mecanismos estándar de Laravel.

### 3) Seguridad y autorización

- Autenticación con Laravel Breeze.
- Autorización por roles/permisos con `spatie/laravel-permission`.
- Protección de rutas mediante middleware y políticas.

### 4) Persistencia de datos

- Base de datos principal: **MariaDB**.
- Acceso a datos con Eloquent y Query Builder.

### 5) Procesamiento asíncrono

- Uso de colas (`QUEUE_CONNECTION=database`) para tareas en background.
- Worker recomendado en desarrollo:

```bash
php artisan queue:work database --queue=default -v
```

## Vista rápida de arquitectura

```text
Usuario
  -> UI (Blade + Livewire/Volt)
  -> Rutas Web (routes/web.php)
  -> Controladores / Servicios
  -> Eloquent / Query Builder
  -> MariaDB

Procesos asíncronos:
  Eventos/acciones -> Cola (database) -> Worker -> Actualización de datos
```

## Stack principal

- PHP 8.4
- Laravel 13
- Livewire 3 + Volt
- MariaDB
- Tailwind CSS 3 + Bootstrap 5
- Pest 4 + PHPUnit 12

## Levantar entorno local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer run dev
```
seeders php artisan db:seed --class=NombreDelSeeder