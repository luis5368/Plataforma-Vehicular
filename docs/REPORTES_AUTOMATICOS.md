# Reportes automáticos: implementación y habilitación

## Causa del dashboard vacío

El código anterior filtraba las distribuciones de departamento, municipio, zona y marca y el mapa regional por `theft_reports.status = validado`. Los conteos proporcionados por el operador (23 `pendiente_validacion`, 2 `resuelto`) no contienen ese estado. Por tanto, las distribuciones activas estaban vacías por la regla aplicada, no por falta de registros. Además, la tendencia mensual anterior mezclaba todos los estados y las zonas estaban limitadas a Guatemala, a diferencia de otros indicadores.

Se inspeccionaron las migraciones, relaciones, controladores, rutas y vistas reales del repositorio. No se consultaron ni reclasificaron las 25 filas de la instalación. Sus valores individuales, relaciones y condiciones actuales no se pueden dar por comprobados a partir de esos conteos.

## Esquema y mapeo propuesto (antes de ejecutar)

La nueva migración **2026_10_18_000003_separate_report_validation_and_case.php** agrega columnas sin eliminar ni modificar `status`, VIN, usuarios o el contenido histórico:

| Estado existente | validation_status | case_status | validation_method |
| --- | --- | --- | --- |
| pendiente_validacion | pendiente | abierto | NULL |
| resuelto | pendiente | resuelto | NULL |
| validado | validado | abierto | legado |
| rechazado | rechazado | abierto | NULL |
| retirado (si se vuelve a aplicar tras una reversión) | pendiente | retirado | NULL |
| otro valor no reconocido | pendiente | abierto | NULL |

`resuelto` no prueba que hubiera una validación anterior: se conserva el cierre y se deja la validación sin acreditar. Los 23 pendientes no se transforman en validados. El mapeo de `validado` conserva el dato previo como legado, sin atribuir una persona ni una fecha de revisión que no consta.

Se agregan `validated_at`, `validated_by` (referencia a usuario), `evidence_reference` y `review_reason`. Las fechas y personas de validación históricas quedan nulas. `status` continúa como proyección compatible para lectores anteriores; las consultas nuevas usan las dos dimensiones separadas. En nuevas operaciones: caso resuelto → `resuelto`; retirado → `retirado`; en otro caso, revisión validada/rechazada/pendiente → `validado`/`rechazado`/`pendiente_validacion`.

## Registro y revisión

- Se conservan `auth`, `active`, `verified`, roles `admin,registro`, propiedad del vehículo, límites de escritura y restricción del módulo de consulta para `registro`.
- El formulario de vehículo sigue siendo el punto de entrada del reporte. VIN de 17 caracteres sin I/O/Q, placa normalizada y única, marca, modelo, departamento, municipio, fecha no futura y descripción se validan en servidor. La publicación automática exige además dirección y referencia documental declarada. Las coordenadas son opcionales, pero deben enviarse en pareja y dentro de sus rangos.
- La referencia documental es un identificador declarado por el reportante; no es una carga de archivo, una comprobación contra autoridad externa ni una prueba independiente del hecho. No se agregó una integración de verificación de evidencia. No deben ingresarse datos personales innecesarios.
- Los reportes nuevos completos quedan con `validation_status=validado`, `case_status=abierto`, método `automatica`, actor y fecha de validación calculados en backend. La creación no acepta decisiones enviadas por el navegador. Los campos de estado y metadatos de `TheftReport` están protegidos contra asignación masiva.
- Datos inválidos o incompletos devuelven errores y revierten toda la transacción: no se publica un reporte parcial como validado. Un histórico pendiente editado permanece pendiente hasta revisión administrativa explícita.
- Se mantiene **un reporte actual por vehículo**, respaldado por el índice único existente de `vehicle_id` y por VIN/placa únicos. Editar el reporte crea una instantánea de auditoría, no otra fila duplicada. No se modelan incidentes múltiples independientes para el mismo VIN en esta entrega.
- La edición de un reporte automático abierto vuelve a comprobar integridad. Registro no puede reabrir ni alterar un caso resuelto, retirado o invalidado. Una decisión administrativa prevalece sobre campos manipulados por el cliente.
- Administración puede corregir, validar, invalidar, resolver y reabrir con motivo. Resolver solo cambia el caso y conserva método, actor y fecha de validación. Invalidar conserva la evidencia del historial previo. Volver a validar un invalidado requiere una decisión administrativa explícita.
- Retirar conserva la fila, su validación y las instantáneas, con caso `retirado`. La eliminación del vehículo conserva su instantánea con el reporte en la bitácora y respeta las relaciones existentes.
- Vehículo, reporte y bitácora se guardan en una transacción, con bloqueo de fila en ediciones. `vehicles.status=reportado` significa que tiene un caso abierto no invalidado; no significa robo confirmado. Los pendientes se identifican por separado.

## Dashboard, consultas y mapa

`ReportAnalytics` comparte filtros exactos de departamento, municipio y zona entre tarjetas, distribuciones, listados regionales y mapa. Cada indicador cuenta VIN o vehículo único; el esquema mantiene un VIN único por vehículo y un reporte actual por vehículo. Los números globales de usuarios y consultas se identifican como globales.

- Abiertos validados: validación `validado` y caso `abierto`, tanto automática como administrativa o legado registrado.
- Resueltos: caso `resuelto`, incluidos históricos sin validación acreditada. Resolver no borra la validación previa.
- Pendientes: validación `pendiente` y caso `abierto`; no se suman a distribuciones de casos abiertos validados.
- Departamento, municipio, zona y marca omiten valores vacíos en su dimensión. La tendencia mensual incluye reportes validados abiertos o resueltos, sin mezclar pendientes ni retirados.
- El mapa incluye el mismo conjunto de casos abiertos validados, limitado a los que tienen ambas coordenadas. Si faltan coordenadas, la tabla y las tarjetas mantienen esos reportes y el mapa explica su falta de datos.
- Las barras accesibles usan valores reales sin dependencias nuevas. Colecciones vacías muestran «Sin datos disponibles»; fallos SQL de dashboard o región responden con un mensaje diferente y HTTP 503, sin exponer la consulta ni credenciales.
- La búsqueda pública permite ver pendientes, resueltos, retirados e invalidados por su estado explícito, muestra el tipo de fuente/método y no expone nombre, correo, referencia documental, dirección o coordenadas. Ningún tipo de validación se presenta como confirmación independiente del hecho.

## Diagnóstico de los 23 pendientes y 2 resueltos

Se preparó `reports:diagnose`, **solo lectura y sin opción de aplicar cambios**. No se ejecutó contra la base local ni de producción. En pruebas se ejecutó únicamente sobre SQLite en memoria.

Cuando el operador lo decida, puede ejecutarlo manualmente:

```powershell
php artisan reports:diagnose
php artisan reports:diagnose --details
```

El resumen devuelve conteos por estado y por bloqueo; `--details` agrega solo IDs de reportes y motivos, sin correos, VIN, descripciones ni referencias documentales. Funciona también antes de agregar las nuevas columnas: los metadatos ausentes se diagnostican como faltantes. No asigna roles, no verifica correos y no guarda reportes ni auditorías. Al ser de solo lectura no requiere reversión.

El esquema anterior no tenía referencia documental ni trazabilidad separada de validación: esos datos no pueden inventarse para las 25 filas. La existencia de descripción o coordenadas por sí sola no los acredita. Administración deberá revisar individualmente:

1. VIN/placa y relación vehículo-reportante; ausencia de reportes repetidos.
2. Autorización actual, cuenta activa y correo verificado del reportante, sin suponer que describen su condición al crear el histórico.
3. Departamento, municipio, dirección, coordenadas si existen, descripción y fecha coherente.
4. Referencia documental proporcionada por la fuente y motivo de cualquier revisión.
5. Los 2 resueltos: conservar su cierre incluso si la validación sigue pendiente. Si se acredita integridad después, usar «Validar integridad» con motivo, sin «Reabrir».

Los 23 pendientes permanecen pendientes hasta una revisión explícita por fila; no hay comando de validación masiva ni regularización automática. Antes de una reclasificación manual, el diagnóstico sirve como vista previa y la bitácora conserva la instantánea anterior y posterior. Una rectificación posterior se hace por otra decisión administrativa motivada, sin borrar la auditoría.

## Habilitación manual sin operaciones destructivas

1. Respalda la base y revisa el mapeo anterior. Confirma con `php artisan migrate:status` que las migraciones anteriores del proyecto ya están aplicadas. No ejecutes seeders ni recrees la base.
2. Comprueba primero esta migración contra una copia de pruebas de tu instalación MySQL. Esta sesión solo la probó en SQLite en memoria; la compatibilidad con tu versión/configuración MySQL queda pendiente.
3. Si la base ya está en el esquema anterior esperado, aplica **solo la nueva migración**:

```powershell
php artisan migrate --path=database/migrations/2026_10_18_000003_separate_report_validation_and_case.php
npm.cmd run build
```

No ejecutes `migrate:fresh`, `db:seed` ni una migración antigua de credenciales para habilitar este cambio. La nueva migración no modifica usuarios ni reclasifica los pendientes como validados.

4. Inicia sesión con una cuenta `registro` activa y verificada. Registra un vehículo/reporte completo con referencia y dirección reales proporcionadas por la fuente. Revisa la fuente y método automático en el detalle.
5. Con `consulta` o `admin`, busca el VIN y revisa dashboard y región usando los mismos filtros. Si no hay coordenadas, no se colocará un punto inventado en el mapa.
6. Revisa los históricos individualmente con administración, después del diagnóstico opcional de solo lectura. Es normal que, tras migrar y antes de crear/revisar un reporte completo, las distribuciones sigan vacías: no se rellenan con los 23 pendientes.

El `down()` preparado conserva la proyección `status` pero elimina las columnas nuevas. No se recomienda ejecutarlo sobre datos nuevos sin exportar antes esos metadatos y conservar el respaldo y la bitácora; no se ejecutó ningún rollback real ni se prescribe uno como paso de habilitación.

## Archivos principales

Migración nueva; `TheftReport`; `ReportLifecycle`; `ReportAnalytics`; `DiagnoseReports`; controladores de vehículos, dashboard, consultas y búsqueda pública; `VehicleSearch`; formularios/detalles de vehículos; vistas de dashboard, región y consulta; componentes de estado/fuente, filtros, barras y error; pruebas `AutomaticReportsTest` y ajustes de `PlatformTest`.

Los cambios locales previos de autenticación y correo se conservaron. No se editaron `.env`, usuarios, roles ni contraseñas. No se hicieron commit, push o despliegue.

## Validación ejecutada

- `php artisan test --compact`: **65 pruebas aprobadas, 495 aserciones**. Las migraciones y el comando de diagnóstico se probaron únicamente en SQLite en memoria.
- `npm.cmd run build`: Vite aprobado, 58 módulos; no se agregaron dependencias.
- `php artisan route:list` sobre vehículos, dashboard y región: se conservan autenticación, cuenta activa, verificación, roles y límites de escritura.
- `php artisan view:cache`: compilación Blade aprobada; después se limpió la caché de vistas generada.
- Laravel Pint aplicado a los archivos PHP de este cambio; `git diff --check` sin errores.

Quedan pendientes la prueba de la migración en una copia MySQL de la instalación y el diagnóstico manual de los registros históricos reales. No se ejecutó `reports:diagnose` ni la migración nueva contra la base local o de producción.
