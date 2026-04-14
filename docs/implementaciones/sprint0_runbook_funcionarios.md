# Sprint 0 - Runbook de Ejecucion (Produccion)

## Objetivo

Ejecutar el Sprint 0 del plan de funcionarios con respaldo verificable y rollback listo antes del reset de la tabla.

Este runbook esta pensado para MariaDB (motor actual del proyecto).

---

## Alcance de Sprint 0

- Respaldar tabla actual `funcionarios` en SQL.
- Registrar evidencia de uso actual.
- Preparar y validar procedimiento de rollback.
- Definir ventana de mantenimiento para Sprint 1.

---

## Pre-requisitos

- Acceso SSH al servidor de produccion.
- Credenciales de BD con permisos de lectura/escritura.
- Espacio en disco suficiente para backups.
- Ruta segura para almacenar respaldos (idealmente fuera de la app).

---

## Variables sugeridas

Configurar estas variables en la sesion antes de ejecutar comandos:

```bash
export DB_HOST="127.0.0.1"
export DB_PORT="3306"
export DB_NAME="nombre_bd_produccion"
export DB_USER="usuario_bd"
export DB_PASS="password_bd"
export BACKUP_DIR="/var/backups/4totop"
export TS="$(date +%Y%m%d_%H%M%S)"
```

---

## Paso 1 - Crear carpeta de backup

```bash
mkdir -p "$BACKUP_DIR"
ls -lah "$BACKUP_DIR"
```

Resultado esperado: carpeta existente y accesible.

---

## Paso 2 - Backup SQL de la tabla funcionarios

```bash
MYSQL_PWD="$DB_PASS" mysqldump \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --single-transaction \
  --skip-lock-tables \
  "$DB_NAME" funcionarios > "$BACKUP_DIR/funcionarios_pre_reset_${TS}.sql"
```

Validar backup:

```bash
ls -lah "$BACKUP_DIR/funcionarios_pre_reset_${TS}.sql"
wc -l "$BACKUP_DIR/funcionarios_pre_reset_${TS}.sql"
```

Resultado esperado: archivo creado y no vacio.

---

## Paso 3 - Evidencia rapida de datos actuales

```bash
MYSQL_PWD="$DB_PASS" mysql \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --database="$DB_NAME" \
  --execute="SELECT COUNT(*) AS total_funcionarios FROM funcionarios;"
```

Opcional (muestra de filas):

```bash
MYSQL_PWD="$DB_PASS" mysql \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --database="$DB_NAME" \
  --execute="SELECT id, nombre FROM funcionarios ORDER BY id LIMIT 10;"
```

Guardar salida en ticket/documento de cambio.

---

## Paso 4 - Validar script de rollback (sin ejecutarlo)

Comando de restauracion preparado:

```bash
MYSQL_PWD="$DB_PASS" mysql \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --database="$DB_NAME" < "$BACKUP_DIR/funcionarios_pre_reset_${TS}.sql"
```

Nota: este comando se ejecuta solo ante contingencia.

---

## Paso 5 - Verificacion de ventana y checklist pre Sprint 1

Checklist minimo antes de aplicar el reset de tabla:

- [ ] Backup SQL generado y validado.
- [ ] Conteo de registros pre-cambio documentado.
- [ ] Comando de rollback probado en ambiente seguro o validado sintacticamente.
- [ ] Ventana de mantenimiento confirmada.
- [ ] Responsable tecnico y funcional asignados durante la ventana.

---

## Plan de rollback (si Sprint 1 falla)

1. Detener operacion sobre modulo Funcionarios.
2. Restaurar backup SQL de `funcionarios`.
3. Verificar conteo y muestra de datos.
4. Verificar que vistas dependientes vuelvan a cargar solicitantes/nombres.
5. Registrar incidente y causa raiz.

Comandos de verificacion post-rollback:

```bash
MYSQL_PWD="$DB_PASS" mysql \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --database="$DB_NAME" \
  --execute="SELECT COUNT(*) AS total_funcionarios FROM funcionarios;"
```

---

## Evidencia de cierre de Sprint 0

Completar al finalizar:

- Fecha/hora: __________________________
- Responsable: _________________________
- Archivo backup: ______________________
- Total registros respaldados: __________
- Ticket/acta de cambio: _______________
- Estado Sprint 0: [ ] APROBADO / [ ] OBSERVADO
