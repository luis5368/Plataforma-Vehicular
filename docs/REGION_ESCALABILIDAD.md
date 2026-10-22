# Fase 2: región paginada y mapa por área visible

## Comportamiento

- La tabla muestra **25 vehículos por página**, ordenados por fecha de creación e ID descendente. `withQueryString()` conserva filtros y otros parámetros al paginar. Se mantienen VIN completo, placa, ubicación, fecha, fuente y estado; la búsqueda detallada permanece disponible sin cambios de permisos.
- Los KPI y las distribuciones se calculan sobre **todo el conjunto que coincide con los filtros**, independientemente de la página o área visible del mapa. Los filtros de departamento, municipio y zona siguen siendo coincidencias exactas y se envían mediante el formulario GET existente.
- `ReportAnalytics::activeVehicles()` comparte la selección de casos abiertos validados entre tabla, mapa y distribuciones. Pendientes, invalidados, resueltos y retirados no se convierten en puntos activos. Los resueltos siguen contando según la definición previa del KPI correspondiente.
- La tendencia mensual ahora se agrupa en SQL y cuenta vehículos distintos por mes, conservando su período y definición; evita hidratar todos los reportes para agruparlos en PHP. Las expresiones de fecha contemplan las conexiones MySQL y SQLite usadas por el proyecto.
- El HTML inicial no contiene la lista de puntos. Solo incluye los filtros, la página de la tabla y cuatro límites iniciales calculados con agregación SQL para conservar el encuadre del conjunto filtrado. Sin coordenadas válidas, el mapa inicia centrado en Guatemala y la tabla conserva los registros.

## Endpoint protegido

`GET /consulta-region/puntos`, nombre `consultar.region.map`, conserva `auth`, `active`, `verified` y `role:admin,consulta`. Registro recibe 403; un visitante en navegación normal es redirigido al login. No se agregaron permisos ni rutas de escritura.

Parámetros obligatorios: `south`, `north` entre -90 y 90; `west`, `east` entre -180 y 180; `zoom` entero de 0 a 18. Se requiere sur menor que norte y anchura no nula. Se admiten áreas que cruzan el antimeridiano, con oeste mayor que este. Admite los mismos filtros geográficos que la página. Los valores inválidos responden 422.

La consulta aplica estado activo, filtros, coordenadas y área visible antes de generar la respuesta:

- Zoom **0–13**: agrupación por celdas de una cuadrícula mundial estable según el zoom. SQL calcula conteo, centroide y límites de cada grupo; no se descargan todas las filas para agruparlas en PHP. Tamaño de celda: `360 / 2^zoom / 4` grados. No es una partición territorial oficial ni una distancia métrica uniforme.
- Zoom **14–18**: puntos individuales con coordenadas, VIN, placa, marca/modelo, dirección y texto de fuente, como en el mapa anterior. La consulta selecciona esos campos y únicamente los metadatos de fuente necesarios, sin contraseñas, correos, referencia documental ni detalles ajenos al popup anterior.
- Cada respuesta contiene como máximo **300 elementos**. En zoom lejano un elemento puede representar varios reportes. Si hay más celdas que el límite, se priorizan las de mayor conteo, con desempate estable; en detalle se ordena por ID. La tabla paginada y la búsqueda detallada permiten seguir consultando registros que no caben en una respuesta del mapa.

La respuesta JSON incluye `features` y `meta`: `total_matching` (reportes con coordenadas dentro del área y filtros), `represented_count`, `feature_count`, `limit`, `truncated`, `mode` y `detail_zoom`. Si falta cobertura, `truncated=true` y la interfaz indica claramente que hay resultados sin mostrar. No se interpreta el límite como ausencia de reportes.

Las respuestas usan `Cache-Control: no-store`. Un fallo SQL devuelve HTTP 503 y un mensaje español sin consulta ni credenciales; no responde con una colección vacía válida.

## Frecuencia y navegador

Límites independientes: **30 páginas regionales por minuto** y **120 consultas de puntos por minuto**, por ID del usuario autenticado; el generador de clave usa IP como alternativa. La restricción devuelve 429 y `Retry-After`, sin limitar a todos los usuarios por compartir IP.

El coordinador `regional-map.js` se carga con Vite y reutiliza Leaflet existente. Al terminar desplazamientos o cambios de zoom espera **350 ms**; cancela solicitudes al comenzar un movimiento, combina eventos repetidos y descarta respuestas de una revisión anterior incluso si llega una respuesta después de cancelar. No repite la última consulta exitosa si el área no cambió. Respeta `Retry-After` y no reintenta en bucle.

Los grupos son seleccionables con los mecanismos de marcadores de Leaflet y acercan el mapa. En detalle, los campos de popup siguen pasando por escape de texto antes de construir HTML. Errores de red, sesión, permisos, proveedor o datos se muestran como errores; una colección legítimamente vacía muestra ausencia de puntos con coordenadas en el área visible. Sin JavaScript se conserva la tabla y se explica que el mapa lo requiere.

## Archivos

- `ConsultationController`, `RegionalMapRequest`, `RegionalMap`, `ReportAnalytics`.
- `routes/web.php`, `AppServiceProvider`: ruta nueva y límites.
- `consultations/region.blade.php`, `regional-map.js`, importación en `app.js`, estilo del marcador agrupado en `platform.css`.
- `RegionalPerformanceTest`, ajustes de las comprobaciones anteriores de mapa en `AutomaticReportsTest` y pruebas Node en `tests/js/regional-map.test.cjs`.

Se conservan los cambios locales de fase 1 y de `package.json` / `package-lock.json`. No se añadieron dependencias, migraciones, índices ni cambios a `.env`. No se modificaron datos reales ni se ejecutaron migraciones sobre una base real.

## Límites de validación

Las pruebas usan SQLite en memoria. Los bloqueos y permisos de fase 1 siguen incluidos en la suite completa. Las pruebas Node usan adaptadores controlados de Leaflet, DOM y red para comprobar cancelación, agrupación de eventos, respuestas obsoletas, escape y errores; **no equivalen a una prueba visual en navegador real**.

No hay navegador conectado en esta sesión. Quedan pendientes la interacción real con Leaflet y el proveedor de mapas, mediciones con un volumen representativo en MySQL y revisión de planes de ejecución. La respuesta y la memoria de PHP quedan acotadas, pero las agregaciones, conteos y paginación con offset todavía requieren trabajo proporcional al conjunto filtrado. Si las mediciones lo justifican, una fase posterior puede evaluar índices geográficos/compuestos o paginación por cursor; no se añadieron sin evidencia.

## Resultados ejecutados

- Pruebas dirigidas de región, reportes y autorización de fase 1: **43 aprobadas, 468 aserciones**.
- `php artisan test --compact`: **98 aprobadas, 856 aserciones**.
- `node --test tests/js/regional-map.test.cjs`: **3 aprobadas**.
- `npm.cmd run build`: aprobado, **118 módulos**. `node --check resources/js/regional-map.js`: aprobado.
- Pint aplicado a los archivos PHP de fase 2; `git diff --check` sin errores.
- Blade compilado correctamente y caché de vistas de comprobación limpiada; rutas regionales revisadas con sus middleware y límites.

No se realizaron commit, push, despliegue ni consultas de diagnóstico sobre los datos reales.
