# Parches Propuestos

Este documento contiene diffs sugeridos para remediar hallazgos prioritarios.  
No aplica cambios al producto en esta fase; solo propuesta tecnica.

## Parche 1

Archivo afectado: `config/imap.php`  
Objetivo del cambio: Eliminar secretos hardcodeados y forzar transporte seguro.  
Riesgo que corrige: Exposicion de credenciales y MITM en IMAP/POP3.

```diff
--- a/config/imap.php
+++ b/config/imap.php
@@
-'default' => [
-    'host'          => 'pop3.mail.pjud',
-    'port'          => 110,
-    'encryption'    => 'notls',
-    'validate_cert' => false,
-    'username'      => '***',
-    'password'      => '***',
-    'protocol'      => 'pop3',
-],
+'default' => [
+    'host'          => env('IMAP_HOST'),
+    'port'          => (int) env('IMAP_PORT', 995),
+    'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
+    'validate_cert' => filter_var(env('IMAP_VALIDATE_CERT', true), FILTER_VALIDATE_BOOL),
+    'username'      => env('IMAP_USERNAME'),
+    'password'      => env('IMAP_PASSWORD'),
+    'protocol'      => env('IMAP_PROTOCOL', 'pop3'),
+],
```

## Parche 2

Archivo afectado: `app/Providers/AppServiceProvider.php`  
Objetivo del cambio: Eliminar bypass de privilegios por correo fijo.  
Riesgo que corrige: Escalada total de privilegios por cuenta comprometida.

```diff
--- a/app/Providers/AppServiceProvider.php
+++ b/app/Providers/AppServiceProvider.php
@@
-private const FIXED_SUPERADMIN_EMAIL = '***';
@@
 Gate::before(function ($user, string $ability) {
-    if ($user->email === self::FIXED_SUPERADMIN_EMAIL) {
-        return true;
-    }
-
     return $user->hasRole('SuperAdmin') ? true : null;
 });
```

## Parche 3

Archivo afectado: `database/seeders/SuperAdminSeeder.php`  
Objetivo del cambio: Eliminar password por defecto debil.  
Riesgo que corrige: Compromiso de cuenta privilegiada por credenciales triviales.

```diff
--- a/database/seeders/SuperAdminSeeder.php
+++ b/database/seeders/SuperAdminSeeder.php
@@
-$password = (string) env('SUPERADMIN_PASSWORD', str()->random(48));
+$password = (string) env('SUPERADMIN_PASSWORD', '');
+
+if ($password === '' || mb_strlen($password) < 14) {
+    throw new RuntimeException('SUPERADMIN_PASSWORD debe estar definido y tener al menos 14 caracteres.');
+}
```

## Parche 4

Archivo afectado: `routes/web.php` y `app/Http/Controllers/PrograMailController.php`  
Objetivo del cambio: Evitar accion con efecto via GET y aplicar CSRF nativo.  
Riesgo que corrige: Envio involuntario/abusivo (CSRF logico).

```diff
--- a/routes/web.php
+++ b/routes/web.php
@@
-Route::get('/programacion/mail/{fecha}', [PrograMailController::class, 'enviarProgramacionPorCorreo'])
+Route::post('/programacion/mail/{fecha}', [PrograMailController::class, 'enviarProgramacionPorCorreo'])
     ->where('fecha', '\\d{4}-\\d{2}-\\d{2}')
     ->middleware(['auth', 'can:navbar.audiencias.editable'])
     ->name('programacion.mail');
```

## Parche 5

Archivo afectado: `app/Http/Controllers/PrograMailController.php`  
Objetivo del cambio: Evitar exponer mensajes internos al usuario.  
Riesgo que corrige: fuga de informacion tecnica.

```diff
--- a/app/Http/Controllers/PrograMailController.php
+++ b/app/Http/Controllers/PrograMailController.php
@@
-return back()->with('error', 'Fallo envío: '.$ex->getMessage());
+return back()->with('error', 'Fallo de envío. Contacta al administrador del sistema.');
@@
-return back()->with('error', 'Fallo envío (lista): '.$ex->getMessage());
+return back()->with('error', 'Fallo de envío en difusión. Contacta al administrador del sistema.');
```

## Parche 6

Archivo afectado: `routes/console.php`  
Objetivo del cambio: desactivar comando obsoleto inseguro del scheduler.  
Riesgo que corrige: riesgo operativo y uso continuado de flujo POP3 no seguro.

```diff
--- a/routes/console.php
+++ b/routes/console.php
@@
-Artisan::command('correo:leer-ultimos-schedule', function () {
-    Artisan::call('correo:leer-ultimos');
-})->purpose('Leer correo de audiencias diariamente')->everyMinute();
+// Comando obsoleto deshabilitado por riesgo de seguridad.
+// Si se requiere reactivacion, hacerlo solo con IMAP/POP3 seguro y credenciales gestionadas por vault.
```

## Parche 7

Archivo afectado: `info.php`  
Objetivo del cambio: eliminar superficie de fingerprinting.  
Riesgo que corrige: exposicion de metadatos de plataforma.

```diff
--- a/info.php
+++ /dev/null
@@
-<?php
-phpinfo();
```

## Parche 8 (estructural)

Archivo afectado: `app/Livewire/DocumentosBackofficePage.php` y flujo de descargas  
Objetivo del cambio: mover documentos a almacenamiento privado y servirlos con autorizacion por recurso.  
Riesgo que corrige: acceso directo a documentos por URL publica.

```diff
--- a/app/Livewire/DocumentosBackofficePage.php
+++ b/app/Livewire/DocumentosBackofficePage.php
@@
-$storedPath = $file->storeAs('documentos/'.$section, $filename, 'public');
-return '/storage/'.$storedPath;
+$storedPath = $file->storeAs('documentos/'.$section, $filename, 'local');
+return $storedPath;
```

Nota: este parche requiere endpoint de descarga firmado/autorizado para no romper UX.
