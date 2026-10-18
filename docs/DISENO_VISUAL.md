# Identidad visual de Plataforma Vehicular

El rediseño usa la imagen proporcionada solo como referencia estética: azul marino, luz cian, superficies translúcidas y composición de acceso con beneficios y vehículo. No se incorporaron sus fotografías, logotipos, estadísticas, afirmaciones comerciales ni datos.

## Organización

- `resources/css/platform.css`: fuente única del tema. Tokens `--pv-*` para fondo, paneles, campos, texto, colores semánticos, radios, sombra, espaciado y familia tipográfica. Incluye estilos compartidos de tarjetas, botones, tablas, paginación, formularios, alertas, gráficas y controles del mapa; adaptaciones para escritorio, tablet y móvil.
- `resources/css/app.css`: importa Bootstrap e iconos locales, el tema y las utilidades Tailwind existentes. Se desactiva únicamente el reset `preflight` de Tailwind para que no reemplace el sistema de componentes Bootstrap.
- `resources/views/layouts/assets.blade.php`: un único punto de carga de CSS y JavaScript mediante Vite. El antiguo `public/css/platform.css` se trasladó al árbol de fuentes; no hay dos copias del tema.
- `layouts/app`, `layouts/public`, `layouts/auth` y el adaptador `layouts/guest`: una misma identidad para paneles, navegación pública, formularios de acceso y las vistas Breeze. Los controles de navegación conservan sus comprobaciones de rol.
- `resources/js/app.js`: utiliza Bootstrap y Alpine ya instalados. Añade controles de visibilidad de contraseña, estado visual de envío y recuperación al volver mediante el historial. No cambia rutas ni el contenido enviado por los formularios.
- Componentes de botones, campos y etiquetas: conservan atributos y slots; cambian únicamente su presentación.

## Cobertura

Landing y consulta pública; login; registro y elección de cuenta; recuperación, restablecimiento, confirmación y verificación de correo; dashboards de los roles; formularios de vehículos/reportes; usuarios y roles; bitácora; tablas, filtros, estados vacíos, errores, alertas, paginación, barras y mapas.

Las superficies comunes aplican el tema a las vistas existentes sin reescribir sus consultas. Los formularios conservan CSRF, métodos HTTP, nombres, valores anteriores y errores. Google solo aparece en las pantallas de acceso/registro si existe la ruta OAuth ya implementada. No se añadieron integraciones ni credenciales.

## Assets y procedencia

No había una fotografía local adecuada. Se crearon dos componentes originales:

- `components/automotive-scene.blade.php`: ilustración SVG de un vehículo genérico con carretera, relieve estilizado y luces cian; fondo de cuadrícula con CSS. No copia un vehículo de marca ni representa un mapa geográfico oficial. El texto Guatemala es contextual, no un origen de datos estadísticos.
- `components/brand.blade.php`: composición tipográfica del nombre existente con iconos de automóvil y búsqueda.

La ilustración y el tema son código original generado para este proyecto; no utilizan imágenes de terceros. Se reutilizan Bootstrap y Bootstrap Icons instalados, ambos con licencia MIT, compilados por Vite junto con las fuentes de iconos locales. La tipografía usa la pila del sistema, sin descargar fuentes externas. Se retiraron de los layouts los CDN de Bootstrap y fuentes que ya se habían utilizado.

Los mapas funcionales mantienen sus proveedores existentes de Leaflet, geocodificación y OpenStreetMap, incluyendo atribución; no se cambiaron consultas, coordenadas ni proveedores para el rediseño. No se agregaron nuevas dependencias ni fotografías externas.

## Accesibilidad y estados

- Etiquetas visibles, enlaces para saltar al contenido, nombres accesibles en controles de menú y contraseña, y foco de teclado cian visible.
- Texto claro sobre superficies oscuras y estados semánticos que conservan sus etiquetas, no solo el color.
- Áreas de interacción de al menos 44 px en los controles principales y reorganización de navegación y composición de acceso en pantallas pequeñas.
- Hover, foco, desactivado, envío en curso, errores y estados vacíos. No se generan resultados, métricas, ubicaciones o reportes para llenar las pantallas.
- La preferencia `prefers-reduced-motion` desactiva animaciones y transiciones. Los paneles tienen un fondo sólido alternativo si el navegador no soporta desenfoque.

## Validación y límites

La suite de Laravel usa SQLite en memoria para probar los flujos existentes; no se ejecutan migraciones sobre MySQL para habilitar este diseño. La compilación Vite incluye estilos, iconos y JavaScript locales. La compilación de Blade comprueba las vistas.

No hay navegador conectado en esta sesión: no se ha validado visualmente login, landing, dashboard ni administración en escritorio o móvil. Los tamaños responsive están implementados en CSS, pero su inspección visual y pruebas reales de teclado, lector de pantalla y contraste quedan pendientes en el navegador del operador. No falta una fotografía para utilizar el diseño: la escena CSS/SVG es la solución final prevista.

Para actualizar una instalación con el código actual, ejecuta `npm.cmd run build` (o `npm run build`) y, si mantiene vistas compiladas antiguas, `php artisan view:clear`. Este rediseño no requiere migraciones, seeders ni modificaciones de `.env`.

Resultados de esta entrega: **65 pruebas Laravel aprobadas, 495 aserciones**; Vite compiló **117 módulos** con CSS, JavaScript y fuentes de iconos locales; Blade compiló correctamente; `node --check resources/js/app.js` y `git diff --check` sin errores. La caché de vistas creada para comprobar la compilación se limpió después. Estos controles no sustituyen la revisión visual pendiente.
