# Sprint 6 - Consumo de datos globales (correo y cumpleanos)

## Objetivo

Dejar una capa reutilizable para que cualquier subsistema pueda obtener:

- destinatarios por contexto/rol para notificaciones,
- cumpleaneros por dia o por mes.

La implementacion base se concentra en `App\Services\FuncionarioCatalog`.

## Servicio disponible

Archivo: `app/Services/FuncionarioCatalog.php`

Metodos clave:

- `rolesForContext(string $context): array`
- `nombresByContext(string $context): array`
- `emailsByContext(string $context): array`
- `destinatariosByContext(string $context): array`
- `destinatariosByRoles(array $roles): array`
- `cumpleanerosDelDia(CarbonInterface|string|null $date = null): array`
- `cumpleanerosDelMes(int|string $month): array`

## Contextos disponibles

Definidos en `config/funcionarios.php`:

- `audiencias.jueces`
- `audiencias.acta_anfitrion`
- `audiencias.ttp`
- `audiencias.encargado_causa`
- `turnos.magistrados`
- `turnos.funcionarios`
- `turnos.jefatura`
- `oficios.solicitantes`

## Formato de destinatarios

`destinatariosByContext(...)` y `destinatariosByRoles(...)` retornan una lista de arreglos con:

- `nombre`
- `email`
- `rol`
- `rol_label`

Notas:

- Se elimina duplicidad de correos automaticamente (`unique` por email).
- El orden de salida sigue `nombre` ascendente.

## Formato de cumpleanos

`cumpleanerosDelDia(...)` y `cumpleanerosDelMes(...)` retornan:

- `nombre`
- `email`
- `rol`
- `rol_label`
- `fecha_cumpleanos` (`Y-m-d`)

## Ejemplos de uso

```php
use App\Services\FuncionarioCatalog;

$catalog = app(FuncionarioCatalog::class);

$destinatariosOficios = $catalog->destinatariosByContext('oficios.solicitantes');
$correosTurno = $catalog->emailsByContext('turnos.funcionarios');

$cumpleDelDia = $catalog->cumpleanerosDelDia(now());
$cumpleDeAbril = $catalog->cumpleanerosDelMes(4);
```

## Reglas de implementacion para otros subsistemas

- No hardcodear listas de correos o nombres.
- Siempre usar contexto (`...ByContext`) antes que roles directos.
- Si no existe contexto, crearlo en `config/funcionarios.php` y reutilizarlo.
- Validar que el subsistema soporte lista vacia (sin destinatarios/cumpleaneros).
