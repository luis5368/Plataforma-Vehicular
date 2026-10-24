# Ubicación reportada del robo en consulta individual

Actualización: **2026-10-24**. La consulta autenticada por VIN o placa muestra un mapa individual cuando existe un reporte y sus coordenadas son válidas. No se cambiaron rutas, roles, filtros, estados, publicación automática ni fotos privadas. La consulta pública conserva su visibilidad anterior y no incorpora este mapa.

## Fuente de datos y captura

En el esquema existente, `theft_latitude` (decimal 10,8), `theft_longitude` (decimal 11,8) y `theft_report_address` están en `vehicles`. Son los campos específicos del robo usados por el mapa regional; no se sustituyen por una ubicación general, por la IP ni por coordenadas estimadas del departamento/municipio.

Los formularios actuales de creación y edición ya ofrecen Leaflet con selección y arrastre de marcador, y envían `theft_latitude` / `theft_longitude`. `VehicleController` valida rangos y presencia conjunta y persiste ambos campos. La prueba nueva confirma captura/persistencia y publicación automática. **No se añadió un selector duplicado ni una migración.** Las migraciones pendientes de fotos/sesión única de la entrega anterior siguen siendo necesarias para ese código; este cambio no las aplica ni sustituye.

## Comportamiento

- Sección **«Ubicación reportada del robo»**, visible solo en el resultado detallado autorizado con un reporte.
- Un marcador centrado en las coordenadas guardadas, zoom inicial 15. Acepta coordenadas cero y verifica ambos límites geográficos.
- Dirección del robo, municipio y departamento se muestran solo cuando están registrados, tanto en texto accesible como en el popup. Se escapan en Blade y JavaScript.
- Mensaje: **«No se registró una ubicación con coordenadas para este reporte»** si falta cualquiera de las coordenadas o están fuera de rango. No se crea un mapa, no se consulta geocodificación y no se coloca un punto por defecto.
- Aviso de que la ubicación es declarada y **el mapa no verifica por sí mismo que el hecho ocurrió**. Una biblioteca no disponible produce un mensaje de fallo distinto del estado sin coordenadas.

## Reutilización regional

`components/leaflet-map.blade.php` centraliza contenedor responsive, estado accesible, alternativa sin JavaScript y carga única de Leaflet 1.9.4. Lo usan la vista regional y el componente individual `reported-theft-location`.

`map-core.js` comparte configuración de Leaflet, mosaicos OpenStreetMap, atribución, máximo de zoom y escape de texto. El mapa regional conserva centro/encuadre, endpoint protegido, filtros, agrupación, límite de elementos, debounce, cancelación, manejo de respuestas obsoletas y mensajes de errores/omisiones. El individual no solicita el endpoint regional ni descarga puntos ajenos: usa únicamente el reporte ya autorizado de la página.

Las fuentes externas de Leaflet/mosaicos son las que ya utilizaba el proyecto. No se instalaron paquetes ni se cambió de proveedor. La disponibilidad visual de los mosaicos depende de esos proveedores y de la conectividad del navegador.

## Verificación y límite visual

Pruebas nuevas: `IndividualTheftMapTest` y `reported-theft-map.test.cjs`. Se adapta el banco de pruebas regional para cargar el helper compartido sin sustituir la lógica de red probada.

Se cubren VIN y placa, coordenadas presentes/parciales/ausentes/inválidas/cero, texto escapado, ausencia de reporte, roles y consulta pública sin ampliación de datos. En JavaScript se comprueba un solo marcador, centro exacto, escape del popup, proveedor compartido y ausencia de geocodificación o fetch de ubicación. Las pruebas regionales existentes continúan comprobando filtros, paginación, KPI, límites y estados de fallo.

Resultados ejecutados:

- Dirigidas de mapa individual y región: **27 pruebas, 294 aserciones**, aprobadas.
- Suite Laravel completa: **129 pruebas, 1265 aserciones**, aprobadas; SQLite en memoria, **13,722 s**, sin omisiones. GD ya estaba habilitado en el PHP CLI al realizar esta revisión; no se modificó PHP.
- JavaScript: **8 pruebas** aprobadas; tres regionales, dos de fotos y tres nuevas del mapa individual.
- Vite **7.3.6**: build correcto, **121 módulos**, 2,13 s; sin cambios en manifiestos de paquetes.
- Blade, cachés de configuración/rutas, arranque con cachés y limpieza: **9 comprobaciones** del auditor aislado aprobadas, sin cargar `.env`.
- Pint de la prueba PHP nueva y `git diff --check`: correctos; solo avisos de normalización CRLF en vistas existentes.

**Revisión visual pendiente:** el inventario de la herramienta devolvió cero navegadores; los intentos de abrir el navegador integrado y Chrome devolvieron «Browser is not available». No se dispone de capturas ni se afirma haber revisado visualmente ninguna de las dos pantallas. Las pruebas con adaptadores Leaflet no sustituyen esa revisión.

Cuando se conecte un navegador, en un entorno de staging autorizado con datos sintéticos:

1. Entrar como consulta/admin; buscar por VIN y después por placa un reporte con coordenadas. Confirmar marcador, popup, dirección y advertencia en escritorio y móvil, navegación por teclado y carga de mosaicos.
2. Buscar un reporte sin coordenadas, aunque tenga dirección: comprobar el mensaje exacto y ausencia de marcador, búsquedas automáticas o posición estimada.
3. Abrir la región con los mismos filtros: verificar encuadre, puntos/grupos, zoom/desplazamiento, paginación y mensajes de omisiones. Confirmar ausencia de errores de consola y regresiones en la carga diferida.

No se leyó `.env`, no se accedió a usuarios/base reales ni se ejecutaron migraciones en ellos. Se conservan los cambios locales anteriores; sin commit, push ni despliegue.
