# Sprint 7 - QA, despliegue y cierre

## Estado

- Sprint 7 ejecutado en entorno de desarrollo.
- QA tecnico completado con pruebas focalizadas de los cambios transversales.
- Checklist de despliegue y rollback preparado para produccion.

## QA tecnico ejecutado

### Formato (Pint)

Comando ejecutado:

```bash
./vendor/bin/pint --test app/Http/Controllers/SistOficioLibertadesController.php app/Http/Controllers/TurnoController.php app/Http/Controllers/addfuncionarioController.php app/Livewire/AudienciaForm.php app/Livewire/AudienciasCortasBatch.php app/Livewire/LecturasSentenciaBatch.php app/Models/Funcionario.php app/Services/FuncionarioCatalog.php tests/Feature/FuncionarioCatalogTest.php tests/Feature/SistOficioLibertadesSolicitantesTest.php tests/Feature/TurnoRolesValidationTest.php tests/Feature/AudienciasRolesCatalogTest.php
```

Resultado:

- OK (sin errores de formato en archivos intervenidos).

### Pruebas funcionales y regresion

Comando ejecutado:

```bash
php artisan test tests/Feature/FuncionarioCatalogTest.php tests/Feature/SistOficioLibertadesSolicitantesTest.php tests/Feature/TurnoRolesValidationTest.php tests/Feature/AudienciasRolesCatalogTest.php tests/Feature/Security/AuthorizationRoutesTest.php
```

Resultado:

- 17 tests passed
- 64 assertions

Cobertura funcional validada:

- Catalogo global de roles/contextos.
- Solicitantes por rol en Oficio/Libertad.
- Validacion por rol en Turnos.
- Programacion Diaria con entradas sugeridas + texto libre (Audiencias y batch).
- Rutas publicas/seguridad sin regresiones.

## Checklist de despliegue a produccion

### Pre-despliegue

- [ ] Confirmar Sprint 0 completado en produccion (backup y rollback listos).
- [ ] Confirmar ventana de mantenimiento para reset de tabla `funcionarios`.
- [ ] Confirmar lista de ~25 funcionarios para carga inicial manual.
- [ ] Confirmar responsables tecnico y funcional durante la ventana.

### Ejecucion tecnica

1. Activar ventana de mantenimiento.
2. Deploy de codigo.
3. Ejecutar migraciones:

```bash
php artisan migrate --force
```

4. Cargar funcionarios base desde interfaz `addfuncionario` (nombre, rol, correo, cumpleanos).
5. Limpiar cache si corresponde:

```bash
php artisan optimize:clear
```

### Verificacion post-despliegue

- [ ] Funcionarios: alta/listado/eliminacion con 4 campos.
- [ ] Oficio/Libertad: solicitantes filtrados por rol permitido.
- [ ] Turnos: magistrados/funcionarios/jefatura con listas por rol.
- [ ] Programacion Diaria: sugerencias por rol + posibilidad de escribir nombre externo.
- [ ] Sin errores criticos en logs aplicacion.

## Plan de rollback

Si falla el despliegue:

1. Detener operacion del modulo afectado.
2. Restaurar backup SQL de `funcionarios` generado en Sprint 0.
3. Validar conteo y muestra de datos restaurados.
4. Verificar carga de vistas dependientes (Oficio/Libertad, Turnos, Audiencias).

Referencia operativa:

- `docs/implementaciones/sprint0_runbook_funcionarios.md`

## Evidencia de cierre de Sprint 7

- QA tecnico: completado.
- Pruebas focalizadas: completadas.
- Checklist de despliegue y rollback: documentado.
