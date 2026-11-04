# Importación masiva sintética, solo STAGING

Fecha: **2026-10-29**. No se ejecutó ninguna migración ni importación contra bases reales, el VPS o MySQL remoto. No se leyó `.env`. El único archivo local previo sin seguimiento era el Excel corregido; se conservó sin modificar y se excluyó de Git. No hay paquetes nuevos, commit, push ni despliegue.

## Entrada inspeccionada y exportación

Libro: `Base_Vehiculos_Robo_2024_2026_STAGING_CORREGIDA.xlsx`. Se inspeccionó **CARGA_STAGING_CORREGIDA: 7,693 filas, 43 columnas**. **IMPORT_STAGING no se exporta ni importa.** El comando consume CSV, no Excel. No se ejecutan fórmulas ni macros; el exportador rechaza fórmulas en la hoja de carga y conserva valores literales, celdas vacías y referencias de columnas.

Archivos principales: `ImportStagingVehicles` (comando), `StagingVehicleImport` (guardas/parser/validación/escritura), `StagingImportException`, `config/staging_import.php`, las dos migraciones descritas abajo y `StagingVehicleImportTest`. El exportador es `scripts/export-staging-sheet.ps1`. `Vehicle` añade un cast booleano sin habilitar asignación masiva. `IdentifyStaging`/`bootstrap/app.php`, layouts y componentes compartidos incorporan aviso/noindex; vistas de vehículos y consultas y la lista pública de campos añaden la etiqueta sintética. `.gitignore` protege el origen y directorio privado. `VehicleController` y `ReportLifecycle` no se modificaron.

Ruta local privada recomendada, ignorada por Git: `storage/app/private/staging-import/`. No exponer esa carpeta por HTTP ni enlazarla a `public/storage`. `.gitignore` excluye también la ubicación original del libro en `docs/`; **no añadirlo con `git add -f`**. El Excel no se movió ni sobrescribió. El CSV de ensayo quedó en el directorio privado ignorado, sin contenido en fixtures rastreados.

Ejecutar desde el repositorio en Windows, revisando primero los destinos. Estos comandos no conectan a una base:

```powershell
New-Item -ItemType Directory -Force storage/app/private/staging-import
Copy-Item -LiteralPath docs/Base_Vehiculos_Robo_2024_2026_STAGING_CORREGIDA.xlsx -Destination storage/app/private/staging-import/Base_Vehiculos_Robo_2024_2026_STAGING_CORREGIDA.xlsx
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/export-staging-sheet.ps1 -Workbook storage/app/private/staging-import/Base_Vehiculos_Robo_2024_2026_STAGING_CORREGIDA.xlsx -Csv storage/app/private/staging-import/carga-normalizada.csv -NormalizeCoordinateNoise
git check-ignore storage/app/private/staging-import/carga-normalizada.csv
```

`-ExecutionPolicy Bypass` afecta únicamente ese proceso hijo para ejecutar el script revisado; no cambia la política permanente del equipo. El exportador falla si el CSV de destino ya existe: elegir un nombre nuevo, no eliminar ni sobrescribir automáticamente archivos previos. Exporta separador coma, comillas CSV estándar y UTF-8 (con BOM en Windows PowerShell; ambas variantes son aceptadas).

**Precisión del Excel:** dos latitudes están almacenadas con residuos binarios, por ejemplo `14.561585000000001`. El CSV literal fue rechazado por exceder ocho decimales, sin escribir filas. La opción explícita `-NormalizeCoordinateNoise` redondea a ocho decimales **solo si la diferencia numérica es ≤10⁻¹²**; cualquier precisión significativa adicional detiene la exportación y exige corregir el origen explícitamente. En este libro notificó **2 ajustes**. No estima ni geocodifica ubicaciones. El libro permanece intacto y el importador nunca redondea silenciosamente. Las fechas de esta hoja ya son texto ISO `Y-m-d`; no se inventa conversión de fechas seriales de otras hojas.

## Contrato y validaciones

Los 43 encabezados son exactamente los de `StagingVehicleImport::HEADERS`. Se acepta otro orden, pero no columnas ausentes, duplicadas o adicionales. Los nombres se comparan literalmente; no se admiten exportaciones de IMPORT_STAGING. Máximo 20 MiB y 10,000 filas, UTF-8 válido, sin NUL/controles inesperados, sintaxis CSV estricta con comillas escapadas y soporte de saltos dentro de campos. Filas vacías o cantidades de columnas incorrectas se rechazan.

- VIN: `^[A-HJ-NPR-Z0-9]{17}$`; placa: `^[A-Z]{1,2}[0-9]{3}[A-Z]{3}$`, iguales al controlador. No se normalizan claves silenciosamente. Duplicados de VIN, placa o referencia dentro del archivo rechazan todo; VIN/placa existentes en la base también.
- Marca/modelo ≤100; color/zona ≤50; departamento/municipio obligatorios ≤100; dirección obligatoria ≤255; observaciones y descripción ≤5000. Año nulo o entero **1901–año actual**: MySQL YEAR no representa 1900 aunque el formulario normal tenga mínimo 1900; no se cambia ese formulario. Fecha obligatoria ISO real, no futura.
- Coordenadas vacías en pareja o numéricas dentro de rangos, hasta ocho decimales, sin notación ambigua. No se infieren a partir de dirección.
- `synthetic_data=1`, `has_theft_report=1`, `vehicle_status=reportado`, `report_status=activo`, `validation_status=pendiente`, `case_status=abierto`. `validation_method`, `validated_at` y `validated_by` deben estar vacíos. Referencia obligatoria `^STAGING-SINTETICO-[0-9]{6}$`, única dentro del archivo.
- Los IDs CSV de actores solo se comprueban sintácticamente y **se ignoran**. Se usa exclusivamente `--admin-id`, existente, activo, verificado y rol admin. La hoja contiene actores `1`, pero ese valor no es una instrucción ni valor por defecto.
- `source_row_number`, `source_id`, placas originales, candidatos de catálogo/revisión y otras anotaciones fuente no se importan. Se comprueba su tamaño y, cuando aplica, sintaxis de flags, pero no cambian campos de negocio ni estados. `source_row_number` puede repetirse entre bloques de origen y no es una clave del sistema; no se exige unicidad de ese metadato. IDs de vehículos/reportes se generan por la base.

Primero se valida **todo** el CSV en una instantánea acotada en memoria; después se comprueban claves contra la base por bloques de 500. No se inserta una parte válida cuando otra fila falla.

## Esquema real contrastado, no tomado del Excel

Se revisaron VehicleController, ReportLifecycle, modelos, todas las migraciones relevantes, middleware de roles/propietario, auditoría, consulta pública y vistas. No hay un directorio de políticas dedicado: los controles existentes están en middleware/controladores y servicios; no se cambiaron esos permisos.

Se creó una instancia **MySQL Community 8.0.44 nueva, desechable**, con `--no-defaults`, datadir separado ignorado por Git, puerto exclusivo y bind loopback. Se aplicaron las migraciones únicamente en una base nueva de esa instancia y se consultó su metadato real. No es el MySQL de Windows de uso habitual ni el del VPS. La comprobación no afirma que el futuro MySQL remoto tenga ese mismo esquema; el comando hace preflight de columnas/tipos y exige InnoDB antes de importar.

| Campo efectivo | Tipo MySQL comprobado |
| --- | --- |
| vehicles.id / registered_by | bigint unsigned |
| vehicles.vin / plate | varchar(17) / varchar(20), claves únicas existentes |
| brand / model / department / municipality | varchar(100) |
| year / color / zone | year / varchar(50) / varchar(50) |
| theft_report_address | varchar(255) |
| theft_latitude / theft_longitude | decimal(10,8) / decimal(11,8) |
| observations | text |
| vehicles.status | enum activo, reportado, vendido, inactivo |
| vehicles.synthetic_data | tinyint(1), default false, índice |
| vehicles.staging_import_batch_id | bigint unsigned nullable, FK al lote |
| theft_reports.vehicle_id | bigint unsigned, FK y único |
| report_date / description | date / text |
| theft_reports.status | **varchar(40)** tras migración histórica, admite activo |
| validation_status / validation_method / case_status | varchar(30) / varchar(30) nullable / varchar(20) |
| reported_by / updated_by / validated_by | bigint unsigned; los dos últimos nullable |
| validated_at / evidence_reference | timestamp nullable / varchar(255) nullable |

La diferencia de nombres es deliberada: `vehicle_status` → `vehicles.status`; `theft_report_date` → `theft_reports.report_date`; `theft_description` → `description`. Las coordenadas específicas del robo se guardan en `vehicles`, como en el esquema existente.

Migraciones reversibles nuevas:

1. `2026_10_29_000001_add_synthetic_data_to_vehicles`: booleano default false e índice; filas preexistentes conservan false.
2. `2026_10_29_000002_create_staging_import_batches`: hash SHA-256 único, actor, cantidad, resultado/fechas y FK nullable desde vehículos. Permite comprobar cada lote sin listas de IDs personales. `down()` elimina primero FK/columna y después tabla; no es un comando de deshacer importaciones. No revertirlo con datos sin respaldo porque se perderían las etiquetas/referencias del lote.

## Puertas de seguridad antes de ejecutar en staging

1. Entorno **realmente separado**: comprobar privadamente host, puerto, base, usuario, TLS y permisos de staging. `APP_ENV=staging` y una bandera no prueban que las credenciales no sean de producción. No ejecutar nada si esa identidad no está confirmada; nunca corregirlo apuntando a la base de producción.
2. Configuración de ese entorno: `STAGING_IMPORT_ENABLED=true` (booleano estricto mediante configuración Laravel), `STAGING_IMPORT_DATABASE` igual al nombre exacto de la base exclusiva; `DB_URL` ausente. No escribir credenciales en comandos o documentación. La conexión operativa debe ser MySQL; SQLite solo está permitido en pruebas aisladas en memoria con un ajuste interno que no tiene variable `.env`.
3. Aplicar las migraciones nuevas **en una tarea futura autorizada**, después de respaldo y ensayo. No se ejecutaron en staging real en esta tarea. El preflight rechaza columnas/tipos incompatibles o tablas no transaccionales. El registro normal no usa el importador ni `synthetic_data` por asignación masiva.
4. Si hay configuración cacheada, reconstruirla por el procedimiento exclusivo de staging tras revisar las variables; pasar variables de proceso no sustituye un caché existente. No limpiar cachés de otro entorno ni usar `--env=staging` como atajo para reutilizar credenciales desconocidas.
5. Confirmar privadamente el admin real de staging y registrar su ID en una variable local de sesión, sin asumir ID 1. Se exige también para dry-run. Repetir la comprobación bajo bloqueo de fila dentro de la transacción; cambios de rol/desactivación posteriores al dry-run impiden el commit.

## Comandos de ejecución previstos (no ejecutados en staging real)

Exportar previamente como arriba y transferir el CSV por un canal privado al directorio no público de staging. Desde el release correcto en un shell POSIX del staging **ya verificado/configurado**, sustituir los dos valores explícitos siguientes:

```sh
ADMIN_ID='ID_ADMIN_STAGING_VERIFICADO'
CSV_PRIVADO='/RUTA_PRIVADA_STAGING/carga-normalizada.csv'

# Validación predeterminada: ninguna escritura de tablas, ni de bitácora.
php artisan vehicles:import-staging "$CSV_PRIVADO" --admin-id="$ADMIN_ID"

# Solo tras dry-run correcto, respaldo y autorización del lote:
php artisan vehicles:import-staging "$CSV_PRIVADO" --admin-id="$ADMIN_ID" --commit
```

No hay contraseña ni token en esos comandos. Los placeholders deben reemplazarse, no son valores por defecto. El comando muestra solo hash, cantidad, resultado y número de lote; no imprime datos fuente ni excepciones de SQL/DSN. En production, local o con bandera desactivada falla **antes de acceder a la base**, incluso con `--commit`. El CSV por sí solo no puede habilitar la operación.

## Atomicidad, idempotencia y auditoría

El commit usa una sola transacción InnoDB, lotes de 500, inserciones con IDs generados por el motor y relaciones reconstruidas por VIN, sin inferir rangos de autoincrement ni deshabilitar índices/FK. Se repiten actor y duplicados dentro de la transacción. Hash único y claves únicas existentes resuelven carreras mediante rechazo/rollback, no mediante omitir filas.

Todos los vehículos quedan `synthetic_data=true`, `status=reportado`, vinculados al lote. Los reportes nacen **activo/abierto/pendiente**, método y validación nulos. `registered_by`, `reported_by`, `updated_by` provienen del admin autorizado. **No se llama `ReportLifecycle::save()`**, ni se altera la validación automática de registro normal. Los escenarios pendientes no entran en el conjunto regional de reportes activos validados; sí son consultables por identificador según permisos actuales y cuentan como pendientes. No se convierten en reportes reales para llenar el mapa.

El lote y `AuditLog` guardan actor, hash, cantidad, fecha y resultado committed en la misma transacción. Un fallo de auditoría también revierte todo. No se guarda nombre/ruta del archivo, datos fuente, contraseñas o tokens. En un commit rechazado no se insertan filas de auditoría fuera de la transacción: se emite un aviso de log saneado con actor autorizado, hash cuando está disponible, cantidad completa o nula si no llegó a validarse, filas leídas y fecha/resultado. Dry-run no escribe tablas ni log de importación. Bloqueos previos a autenticar al actor solo muestran rechazo en consola. El operador debe conservar los logs privados conforme a la retención de staging.

Repetir los mismos bytes se rechaza por hash, incluso después de borrar vehículos; reexportar con otro BOM/salto de línea cambia el hash, pero los VIN/placas existentes igualmente impiden duplicar entidades. No hay upsert, saltos de filas ni limpieza automática de lotes existentes.

## Identificación visual y noindex

Las tres familias de layouts muestran en staging un aviso de entorno; vehículos importados se etiquetan en listado/detalle/edición, consulta autenticada y resultado público como **DATOS SINTÉTICOS**, no vehículo/denuncia real. La respuesta pública añade solo ese booleano de identificación, no metadatos privados de importación. El campo no es fillable desde formularios normales.

En staging se añade meta robots y cabecera `X-Robots-Tag: noindex, nofollow, noarchive`. No sustituye autenticación/red restringida ni la protección del sitio acordada en el plan de staging. El comportamiento de producción no habilita importaciones ni el aviso general por estas modificaciones.

## Respaldo y comprobación posterior

Antes de un commit real de staging, respaldar consistentemente su base y almacenamiento privado, identificar artefacto/estado de migraciones, comprobar espacio e InnoDB y ensayar restauración a un destino separado. Congelar otras escrituras de ensayo durante la ventana aprobada si se necesita comparar conteos exactamente. No restaurar sobre producción ni hacer borrados manuales para repetir un lote. El rollback transaccional no sustituye un respaldo anterior a la operación.

En una tarea futura autorizada, estas consultas de lectura sobre **la base de staging verificada**, reemplazando `LOTE_CONFIRMADO` por el ID numérico mostrado, permiten comprobar:

```sql
SELECT id, file_hash, actor_id, row_count, result, created_at
FROM staging_import_batches WHERE id = LOTE_CONFIRMADO;

SELECT COUNT(*) AS vehiculos, MIN(synthetic_data) AS todos_sinteticos,
       COUNT(DISTINCT registered_by) AS actores
FROM vehicles WHERE staging_import_batch_id = LOTE_CONFIRMADO;

SELECT r.status, r.case_status, r.validation_status, r.validation_method,
       r.validated_at, r.validated_by, r.reported_by, r.updated_by, COUNT(*) AS cantidad
FROM theft_reports r JOIN vehicles v ON v.id = r.vehicle_id
WHERE v.staging_import_batch_id = LOTE_CONFIRMADO
GROUP BY r.status, r.case_status, r.validation_status, r.validation_method,
         r.validated_at, r.validated_by, r.reported_by, r.updated_by;

SELECT action, user_id, record_id, created_at FROM audit_logs
WHERE table_name = 'staging_import_batches' AND record_id = LOTE_CONFIRMADO;
```

Esperado para este libro: 7,693 vehículos y reportes, booleano mínimo 1, un actor igual al admin, un lote/hash y auditoría de committed; ningún validado por el importador. Consultar un VIN solo en staging para comprobar etiqueta sintética y estado amarillo pendiente; no usar datos reales como control.

## Verificaciones y límites

- Pruebas dirigidas: validaciones de seguridad/actor, dry-run sin cambios, CSV/UTF-8/comillas/encabezados/tamaños/filas, fechas/coordenadas/estados, duplicados, reimportación, fallo del segundo bloque de 501 filas con rollback de todo y lote de 7,693 generado sin el Excel. Incluyen fallo de auditoría y revocación del admin entre lectura y commit.
- Ensayo MySQL 8.0.44 desechable: esquema efectivo e InnoDB contrastados; **CSV exportado del libro suministrado completo** validado sin escrituras, importado con 7,693 relaciones/actores/estados correctos y reimportación rechazada. Hash del CSV de ensayo: `7e75c5a723084ac17269a694b31fa4480f0846b3ba2565e78585170f34fd41c0`. No certifica el esquema del futuro MySQL remoto.
- En el mismo banco MySQL se ensayó otro CSV de 501 filas con claves sintéticas distintas y un fallo forzado en el segundo bloque de reportes: quedaron intactos los 7,693 vehículos/reportes del lote previo, su única fila de lote y su única auditoría; no quedó ninguna fila de la importación fallida ni detalles internos en consola. La instancia desechable se apagó al finalizar; no se detuvo ningún servicio MySQL preexistente.
- Los primeros ensayos fallaron cerradamente por dos residuos de precisión del Excel; se incorporó la normalización explícita documentada. La numeración de fila fuente repetida se trata como metadato, no como una clave de entidad. No se modificó el libro para ocultar validaciones.
- Pendientes de destino: aplicar migraciones en staging autorizado, confirmar configuración/identidad/TLS/admin/respaldos, repetir dry-run allí, autorizar el commit y comprobar el lote. No hay revisión visual nueva en navegador de estas etiquetas ni carga en el VPS. El volumen fue probado en aislamiento, no es una medición de capacidad del hosting ni una prueba de carreras concurrentes del comando.

Resultados finales ejecutados:

| Comprobación | Resultado |
| --- | --- |
| `php vendor/bin/phpunit` con `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, correo/caché/sesión array y TestCase que no carga `.env` | **182 pruebas, 1800 aserciones, 0 fallos/omisiones**, 45,396 s, 120 MB. Incluye 44 casos nuevos, 262 aserciones adicionales, migración reversible/default de datos existentes, fallos/revocación de actor y publicación normal automática sin etiquetas sintéticas manipulables. |
| `node --test tests/js/*.test.cjs` | **8 aprobadas**, sin fallos/omisiones. |
| `npm.cmd run build` | Vite **7.3.6**, **121 módulos**, aprobado. |
| `php tests/Production/cache-audit.php` | **9 comprobaciones** aprobadas: cachés/arranque/middleware y compilación Blade, con entorno/cachés aislados y limpieza posterior. |
| Pint aplicado y `php vendor/bin/pint --test --dirty` | Aprobado. |
| `git diff --check` y `git check-ignore` para libro/CSV | Sin errores de espacios; Excel y CSV privados ignorados, no añadidos al índice. |

Las migraciones ejecutadas por la suite y el banco MySQL afectaron solo destinos aislados. La suite preexistente incluye su preparación interna de roles de prueba; no se ejecutaron seeders generales ni se crearon cuentas reales. No se consultó `migrate:status` contra una base real ni se ejecutó el comando de importación sobre la configuración normal de `.env`.
