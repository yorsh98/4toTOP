# Dependencias con vulnerabilidades

Validado el 15 de septiembre de 2026.

## Estado

| Dependencia | Instalada | Actualizacion recomendada | Riesgo de compatibilidad |
| --- | --- | --- | --- |
| `league/commonmark` | `2.9.0` | `2.10.1` | Bajo |
| `livewire/livewire` | `3.7.15` | `3.8.3` | Bajo |
| `postcss-selector-parser` | `6.1.2` | `6.1.3` | Bajo |
| `browserslist` | `4.28.1` | `4.28.7` | Bajo |
| `baseline-browser-mapping` | `2.10.0` | `2.11.0` o superior | Bajo |

## Hallazgos

### league/commonmark

- No actualizar solo a `2.9.1`: corrige `GHSA-f8fg-pg57-v4j8`, `GHSA-jjv6-8j6v-6j52` y `GHSA-j8pm-gj4c-rq4x`, pero `GHSA-8rr7-cvq3-gmfh` sigue afectando versiones anteriores a `2.10.0`.
- Laravel 13.24.0 requiere `^2.8.1`, por lo que `2.10.1` es compatible.
- No se encontro uso directo de CommonMark en `app/` ni en el codigo PHP del proyecto.
- Los parches no rompen la API, aunque pueden cambiar el HTML de Markdown especialmente malformado o malicioso.

### livewire/livewire

- `3.8.3` es compatible con Laravel 13 y Livewire Volt 1.10.5.
- Corrige `CVE-2026-81887` para las versiones `3.0.0-beta.1` a `3.8.2`.
- La version incluye correcciones para `wire:model.blur` y parsing de query strings.
- No se encontro `wire:model.blur`. Hay componentes que usan query strings, por lo que se deben probar filtros y enlaces compartidos despues de actualizar.

### Dependencias frontend transitivas

- `postcss-selector-parser` llega por Tailwind CSS y `postcss-nested`; `6.1.3` corrige una denegacion de servicio por recursion de AST.
- `browserslist` llega por Autoprefixer; `4.28.7` corrige crecimiento de memoria sin limite y escritura de prototipos.
- `baseline-browser-mapping` llega por Browserslist; `2.11.0` corrige terminacion de proceso con entradas invalidas.
- No hay configuracion propia de Browserslist. La actualizacion podria modificar minimamente el CSS generado segun los navegadores objetivo vigentes.

## Verificacion pendiente tras actualizar

```bash
composer update league/commonmark livewire/livewire --with-all-dependencies
npm update browserslist baseline-browser-mapping postcss-selector-parser
composer audit --locked
npm audit --package-lock-only
npm run build
./vendor/bin/pint --test
```

Ejecutar tambien los tests de los componentes Livewire que usan query strings y verificar manualmente sus filtros y enlaces compartidos.
