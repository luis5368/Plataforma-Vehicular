# Fotografías privadas y una sesión por cuenta

Actualización: **2026-10-23**. Implementación para Laravel **12.69.2** instalado, PHP `^8.2`. No se actualizó Laravel ni se añadieron paquetes. No se leyó `.env`, no se ejecutaron migraciones sobre la base real ni se cambió el driver de sesiones. El esquema se revisó en las migraciones y se comprobó en una base MySQL nueva de pruebas, no mediante acceso a la base del usuario.

## Fotografías y permisos

Los formularios existentes de creación/edición de vehículos usan multipart y admiten fotos opcionales al incluir un reporte. La vista previa permite retirar una selección antes del envío; retirar una selección no borra fotos ya guardadas. La interfaz no conserva archivos tras errores de validación. No se añadió una operación independiente de borrado de fotos.

- Hasta **5 fotos por reporte en total**, no cinco adicionales por edición; comprobación dentro de la transacción que bloquea el vehículo.
- Hasta **5 MB por archivo**, tanto recibido como recodificado; JPEG/JPG, PNG y WebP. Se comprueban extensión, MIME detectado con fileinfo, dimensiones y decodificación real con GD; no se confía en el MIME del navegador.
- Máximo **4096 píxeles por lado y 8 000 000 píxeles**. Antes de decodificar se comprueba además un presupuesto conservador de memoria respecto del límite PHP disponible.
- GD vuelve a codificar los píxeles. No se guardan EXIF/GPS, comentarios, nombre original ni contenido añadido al final del archivo. Se pierde la orientación EXIF; no hay transformación automática para conservarla. No se requiere Imagick ni se conservan originales.
- La publicación automática, el autor y los metadatos de validación del reporte siguen asignándose en el servidor. Las fotos no constituyen acreditación independiente ni aprobación de autoridad.

El disco predeterminado `report_photos` guarda archivos bajo `storage/app/report-photos/reports/photos/`, fuera de `public/`, con nombres aleatorios y sin URL pública o temporal generada. No ejecutar `storage:link` para ese directorio. La tabla `report_photos` conserva reporte, autor de carga, disco, ruta, MIME, tamaño y fechas; las rutas/disco quedan ocultos en su serialización. La referencia al autor impide su eliminación física por cascada; desactivar su cuenta conserva las fotos.

`GET /reportes/fotos/{photo}` exige `auth`, `session.current`, `active`, `verified`, un rol existente autorizado y el limitador independiente `report-photos` (**120/minuto por usuario**, IP alternativa). Las respuestas se transmiten con `no-store`, `nosniff` y un tipo de imagen detectado por el servidor.

- Admin: acceso administrativo.
- Consulta: lectura de las fotos en el mismo alcance que la búsqueda detallada actual, que permite consultar reportes por identificador de vehículo sin limitarse a su autor. No se añadieron permisos de escritura ni se restringió el detalle.
- Registro: solo fotos de reportes propios en vehículos propios; conocer un ID o una ruta no autoriza el acceso. No puede añadir fotos a casos resueltos/retirados ni borrar esos casos.
- Visitante/landing/consulta pública: sin fotos ni rutas internas. La galería se incluye únicamente en detalle de vehículo y resultado de consulta autenticada.

La auditoría de adjuntos registra actor, reporte y cantidad (`attach_report_photos`), sin rutas, nombres originales o binarios. Se mantienen las bitácoras y reglas de eliminación existentes.

## Fallos y limpieza de archivos

MySQL y el disco no comparten transacción. `ReportPhotos` registra primero una entrada durable en `photo_file_deletions` con vencimiento de 24 horas y después escribe cada archivo saneado. La transacción del reporte crea los metadatos y retira esa entrada. Si falla, revierte los registros y limpia lo preparado; si el proceso termina abruptamente, quedan entradas identificables para reconciliar. No se promete atomicidad distribuida ni recuperación sin el proceso de limpieza.

La eliminación permitida pasa por `ReportLifecycle`, registra la limpieza en la misma transacción antes de borrar vehículo/reporte y sus metadatos, y la intenta después del commit. Si el disco falla, el archivo sigue privado y registrado para reintento; no queda un archivo sin seguimiento. Un archivo ya ausente se considera limpiado. Un rollback antes del commit conserva las fotos originales. No borrar reportes mediante SQL o directamente mediante modelos como sustituto de este servicio: una cascada SQL por sí sola no limpia archivos.

`reports:cleanup-photos` reintenta entradas vencidas y devuelve fallo si no logra limpiarlas, sin imprimir rutas. Está programado cada hora, con exclusión de solapamiento, en el scheduler Laravel. **En el hosting hay que habilitar y comprobar el scheduler**, o ejecutar el comando mediante una tarea privada equivalente autorizada. El cron, driver compartido de caché y sus límites quedan pendientes del plan Hostinger. No se configuraron tareas reales. No permitir solicitudes de carga que duren 24 horas ni ejecutar limpiadores arbitrarios de archivos por fecha.

```text
# Solo tras aplicar las migraciones en un entorno autorizado:
php artisan reports:cleanup-photos
# El cron habitual de Laravel ejecuta schedule:run; confirmar ruta PHP y permisos del plan.
```

Para migrar a almacenamiento compatible con S3, el controlador no necesita reescribirse: seleccionar un disco mediante `REPORT_PHOTOS_DISK`, instalar/configurar su adaptador en una tarea autorizada y declarar `visibility=private`. Confirmar bucket privado, IAM, cifrado y disponibilidad desde el hosting. Los metadatos guardan el disco de cada foto: cambiar la configuración no mueve los archivos existentes; planificar su copia y actualización de referencias de forma coherente. No seleccionar el disco `public` ni generar enlaces públicos.

## Una sesión activa por cuenta

`users.active_session_hash` guarda un SHA-256 de un valor aleatorio de 256 bits; la sesión Laravel conserva el valor original. No se persiste el ID de sesión ni el valor original en usuarios/auditoría. El hash se oculta de la serialización y no se acepta por asignación masiva.

Login por contraseña, registro con autenticación y Google OAuth llaman a `ActiveSession::start`. La fila del usuario se bloquea y el hash se sustituye transaccionalmente; ante logins concurrentes gana el último commit. Solo esa sesión puede seguir autenticada. Se comprueban también estado/rol y que la contraseña almacenada no haya cambiado desde la autenticación. El mecanismo no depende de enumerar sesiones `database`, `file` o Redis y funciona con distintos almacenes compartiendo la misma base de usuarios.

`session.current` compara en tiempo constante el valor de la sesión con una lectura actual del hash en la base, en todas las rutas autenticadas (incluidas administración, región, fotos, Perfil, verificación y logout). No se aplica a login, recuperación ni callback OAuth. Una petición que ya estaba ejecutándose puede terminar; la siguiente petición autenticada de la sesión anterior se rechaza.

Al detectar reemplazo/revocación, el middleware cierra solo ese dispositivo, invalida su sesión y renueva CSRF. Redirige al login con un aviso español. No borra el identificador de la sesión nueva ni rota su credencial recordada. En logout válido, una actualización condicional elimina el hash y acceso recordado solo si pertenecen a la sesión que cierra: un logout antiguo no puede invalidar un login nuevo.

Dos pestañas que comparten cookie siguen siendo la misma sesión; dos cuentas distintas no se reemplazan entre sí. La aplicación móvil/navegador y la base deben estar disponibles para verificar el hash; no se permite acceso por fallback si esa comprobación falla.

### Revocación, contraseñas y «Recordarme»

- Desactivar **o reactivar** mediante `AccountLifecycle` borra el hash y revoca la autorización anterior en todos los drivers. Se conserva la eliminación adicional de filas cuando `SESSION_DRIVER=database`; nunca se cambia el driver automáticamente. Las rutas activas siguen bloqueando cuentas desactivadas.
- Cambio de contraseña desde la sesión válida: reemplaza el vínculo y regenera la sesión, manteniendo ese navegador. Restablecimiento mediante enlace: borra el vínculo y exige login nuevo. Cambio de contraseña desde administración: revoca el vínculo de la cuenta afectada, incluso si es la del administrador actual.
- Cada login revoca la credencial anterior de «Recordarme». Si se solicita, se emite una nueva, pero **una cookie recordada sin la sesión vinculada no restaura autorización**: se exige autenticación por contraseña/Google de nuevo. No se permite que un navegador antiguo reclame automáticamente un hash y expulse al nuevo. Esta limitación es deliberada; no equivale a la restauración automática clásica de Laravel cuando se pierde la sesión.
- Tras desplegar las migraciones, todas las sesiones anteriores carecen de vínculo y deben iniciar sesión nuevamente. No se eliminan masivamente sus filas; el recolector del driver aplica su ciclo normal.

## Migraciones, requisitos y respaldo

Migraciones nuevas, aditivas:

1. `2026_10_23_000001_add_active_session_hash_to_users`: añade el hash nullable, sin reescribir usuarios ni contraseñas.
2. `2026_10_23_000002_create_report_photos`: crea `report_photos` y la cola durable `photo_file_deletions`, con FK e índices.

No ejecutar automáticamente en la base real. Respaldar y ensayar sobre una copia autorizada compatible, comprobar permisos DDL y coordinar código/esquema para evitar solicitudes sin las columnas nuevas. No usar `migrate:fresh` ni el modo destructivo del banco histórico de auditoría. Los `down()` eliminan metadatos: no son un rollback de retención seguro después de subir fotos.

**Los archivos privados requieren respaldo junto con la base de datos, aunque estén fuera de MySQL.** Incluir metadatos, archivos de todos los discos utilizados y cola de limpieza. Coordinar una instantánea consistente suspendiendo cargas/borrados/limpieza o usando un mecanismo equivalente probado. Restaurar y comprobar una muestra mediante la ruta autorizada, atribución y correspondencia entre filas y archivos; no ejecutar limpieza sobre una restauración incompleta.

Requisitos adicionales del alojamiento:

- GD con soporte JPEG, PNG y WebP, fileinfo y PDO del motor de destino. GD existe como DLL pero estaba deshabilitado en el CLI local; las pruebas lo activan **solo para ese proceso** con `-d extension=gd`. Imagick no está disponible ni se necesita. Sin GD el formulario sin fotos funciona y la carga recibe un error español.
- `upload_max_filesize` al menos 5 MB, `post_max_size` mayor que 25 MB más formulario/overhead (referencia operativa: 32 MB o más), `max_file_uploads` al menos 5; validar también límite del proxy/web y tiempos. PHP CLI observado: 40M/40M, 20 archivos y memoria 512M; **no certifica PHP web ni Hostinger**. Recomendar un presupuesto PHP de 256 MB o superior y probar fotos máximas con la cuota real; la comprobación de memoria puede rechazar imágenes antes de llegar a esos máximos si no hay presupuesto.
- Escritura privada del usuario PHP sobre el disco, sin publicación ni permisos globales. Configurar monitorización de errores y pendientes de limpieza sin registrar datos sensibles.
- Probar PHP web y CLI, directorio privado, persistencia entre releases, scheduler y respaldo en el plan contratado. No se editaron PHP, Hostinger, CloudPanel, firewall ni DNS.

## Verificación reproducible

La suite carga un directorio de entorno vacío y una clave sintética efímera: no lee `.env`. `actingAs` de las pruebas establece el vínculo que produciría un login; las pruebas de reemplazo usan además sesiones sin ese atajo para comprobar rechazos reales.

```powershell
php -d extension=gd vendor/bin/phpunit --filter='ReportPhotosTest|SingleActiveSessionTest' --no-progress
php -d extension=gd vendor/bin/phpunit --no-progress
node --test tests/js/*.test.cjs
npm.cmd run build
php tests/Production/cache-audit.php
git diff --check
```

Para concurrencia real, el runner existente añade `-SessionOnly`: crea una base **nueva** de nombre generado dentro de la instancia local aislada, aplica solo migraciones hacia delante, confirma tablas/FK y ejecuta cinco pares de procesos de login. No reutiliza ni borra `pv_audit`, no usa `migrate:fresh`, no hace drop/restore y retiene únicamente datos sintéticos. La instancia se apaga al terminar; las barreras solo guardan marcadores de sincronización, nunca tokens ni IDs de sesión.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tests/Production/run-mysql-audit.ps1 -SessionOnly
```

Las pruebas cubren fotos válidas/ausentes, formatos falsos y límites, saneamiento, propiedad/roles, ruta privada, fallos parciales y rollback, cola de limpieza, conservación al desactivar y casos cerrados; también login por contraseña/Google, reemplazo, logout antiguo, pestañas, cuentas distintas, cambio/restablecimiento de contraseña y rutas críticas. Las pruebas Node comprueban retirada del archivo real del input y errores de selección, además del mapa previo.

Pendientes de destino: aplicar migraciones autorizadas, habilitar GD, verificar límites PHP web y cron, restauración consistente de archivos/base, prueba real de dos navegadores y vista previa en escritorio/móvil. No se realizó inspección visual en navegador ni envío SMTP/OAuth real.

### Resultados de esta entrega

- Dirigidas (`ReportPhotosTest|SingleActiveSessionTest`): **18 pruebas, 293 aserciones**, aprobadas.
- Suite completa: **119 pruebas, 1206 aserciones**, aprobadas, **12,195 s**, memoria 64 MB. SQLite en memoria con GD habilitado únicamente en el proceso PHP de prueba; sin pruebas omitidas. No sustituye la comprobación MySQL siguiente.
- MySQL **8.0.44**, aislamiento `REPEATABLE-READ`, modo `-SessionOnly`: **8 comprobaciones** aprobadas, incluidas cinco carreras de login con conexiones/procesos separados y verificación del esquema nuevo con sus dos FK. Todas las migraciones hacia delante se aplicaron en una base nueva sintética. Se confirmó que no se ejecutó el modo `migrate:fresh`; el servidor temporal se apagó.
- JavaScript: **5 pruebas** aprobadas, incluidas tres del mapa anterior y dos de selección/retirada de fotos.
- Vite **7.3.6**: build aprobado, **119 módulos**, 1,55 s. No se modificaron `package.json` ni `package-lock.json` en esta ampliación.
- Auditoría de cachés aislada: **9 comprobaciones** aprobadas; Blade/config/rutas compilan, una aplicación nueva arranca con ellas, los middleware se verifican y las cachés se limpian sin cargar `.env`.
- Pint aplicado a los PHP modificados y `--test --dirty` aprobado; `git diff --check` sin errores (solo avisos CRLF→LF en tres vistas existentes).

Archivos principales: `VehicleController`, `ReportLifecycle`, `ReportPhotos`, `ReportPhotoController`, modelos de fotos/limpieza y sus vistas; `AuthController`, controladores de contraseña/usuario, `ActiveSession`, `SingleActiveSession`, `AccountLifecycle`, `User`, bootstrap/rutas; discos/configuración, scheduler y comando de limpieza. Se añadieron pruebas de funciones/JavaScript, se aisló el entorno de pruebas y se amplió el banco MySQL. La auditoría y el checklist anteriores se conservan como evidencia histórica.
