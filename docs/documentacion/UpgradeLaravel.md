# Upgrade Laravel 11 -> 13 (Proyecto 4toTOP)

## 1) Objetivo

Documentar la actualizacion aplicada en este repositorio desde Laravel 11 hasta Laravel 13, incluyendo decisiones tecnicas, cambios de codigo, comandos ejecutados, validaciones y pendientes operativos.

---

## 2) Alcance

- Upgrade de framework en 2 saltos:
  1. Laravel 11 -> Laravel 12
  2. Laravel 12 -> Laravel 13
- Ajuste de dependencias bloqueantes de Composer.
- Ajustes minimos de compatibilidad en rutas/middleware.
- Verificacion con tests y checks de formato en archivos modificados.

---

## 3) Estado final alcanzado

- Framework final: `laravel/framework v13.4.0`
- Tinker final: `laravel/tinker v3.0.0`
- DataTables final: `yajra/laravel-datatables-oracle v13.0`
- Paquete removido por no uso real: `rappasoft/laravel-livewire-tables`
- Resultado de tests (suite completa): **43 passed**

---

## 4) Resumen de cambios por etapa

## 4.1 Laravel 11 -> Laravel 12

### Dependencias actualizadas

- `php`: `^8.3`
- `laravel/framework`: `^12.0`
- `laravel/tinker`: `^2.11`
- `livewire/volt`: `^1.10`
- `yajra/laravel-datatables-oracle`: `^12.7`
- `laravel/breeze`: `^2.4`
- `laravel/pail`: `^1.2.6`
- `laravel/sail`: `^1.56`
- `nunomaduro/collision`: `^8.9.3`
- `pestphp/pest`: `^4.4`
- `pestphp/pest-plugin-laravel`: `^4.1`
- `phpunit/phpunit`: `^12.5`

### Ajustes de codigo

- Se elimino duplicidad de ruta `/` con nombre `welcome` en `routes/web.php` para evitar comportamiento ambiguo por precedencia.

### Ajuste de testing

- Se fijo `DB_CONNECTION=mysql` en `phpunit.xml` para evitar inestabilidad con configuracion `mariadb` en tests.

---

## 4.2 Laravel 12 -> Laravel 13

### Dependencias actualizadas

- `laravel/framework`: `^13.0`
- `laravel/tinker`: `^3.0`
- `yajra/laravel-datatables-oracle`: `^13.0`

### Dependencias removidas

- `rappasoft/laravel-livewire-tables`
  - Motivo: no se encontraron referencias activas en `app/`, `resources/`, `routes/` ni `tests/`.
  - Limpieza asociada: eliminacion de `config/livewire-tables.php`.

### Ajustes de compatibilidad Laravel 13

- En `routes/web.php`:
  - `VerifyCsrfToken` -> `PreventRequestForgery` en `withoutMiddleware(...)`.
- En `bootstrap/app.php`:
  - se removio bloque legado no ejecutable posterior a `->create();`.

---

## 5) Archivos clave modificados

- `composer.json`
- `composer.lock`
- `routes/web.php`
- `bootstrap/app.php`
- `phpunit.xml`
- `config/livewire-tables.php` (eliminado)

---

## 6) Comandos utilizados (referencia)

## 6.1 Upgrade de dependencias

```bash
composer update -W
composer remove rappasoft/laravel-livewire-tables -W
composer require laravel/framework:^13.0 laravel/tinker:^3.0 yajra/laravel-datatables-oracle:^13.0 -W
```

## 6.2 Validacion framework y rutas

```bash
php artisan --version
composer why-not laravel/framework ^13.0
php artisan optimize:clear
php artisan route:list --except-vendor
```

## 6.3 Validacion de calidad y tests

```bash
./vendor/bin/pint --test bootstrap/app.php routes/web.php
DB_CONNECTION=mysql DB_DATABASE=sistoficiolibertades_test php artisan test tests/Feature/Auth
DB_CONNECTION=mysql DB_DATABASE=sistoficiolibertades_test php artisan test tests/Feature/Security
DB_CONNECTION=mysql DB_DATABASE=sistoficiolibertades_test php artisan test
```

---

## 7) Incidencias encontradas y resolucion

1. **Fallo al correr suites en paralelo con misma BD de pruebas**
   - Sintomas: errores intermitentes `migrations table doesn't exist` y `table already exists`.
   - Causa: ejecucion concurrente de procesos de test sobre la misma base.
   - Resolucion: ejecutar suites de forma secuencial y usar BD dedicada (`sistoficiolibertades_test`).

2. **SQLite in-memory no compatible con migracion que usa IF(...)**
   - Sintomas: `General error: no such function: IF`.
   - Resolucion: validar tests con MySQL/MariaDB, no con SQLite para este proyecto.

---

## 8) Checklist post-upgrade recomendado

- Ejecutar `php artisan migrate --force` en ambiente objetivo.
- Ejecutar `php artisan db:seed --force` si corresponde al flujo del entorno.
- Smoke test manual:
  - login/registro/aprobacion de usuarios,
  - modulos Livewire (`OficioTabla`, `LibertadTabla`, `TablaGuias`),
  - datatables Yajra,
  - envios de correo y rutas con excepcion CSRF.
- Verificar jobs/colas y cron operativos despues del deploy.

---

## 9) Resultado

La migracion Laravel 11 -> 13 quedo aplicada y validada a nivel de framework, dependencias y tests del proyecto, con eliminacion de dependencia no utilizada y ajustes de compatibilidad necesarios para Laravel 13.
