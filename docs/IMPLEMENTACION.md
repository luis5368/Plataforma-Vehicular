# Mejoras de Plataforma Vehicular

## Preparación y activación

1. Conserva un respaldo de la base existente y configura la conexión en `.env`.
2. Ejecuta `php artisan migrate`. No se modificaron migraciones anteriores.
3. Ejecuta `php artisan db:seed --class=RoleSeeder` si todavía no existen los roles. El seeder es reutilizable y no crea cuentas con contraseñas de demostración.
4. Crea el primer administrador con `php artisan app:create-admin`. Pide nombre, correo y contraseña con confirmación oculta; no pasa contraseñas como argumentos ni las escribe en archivos. El operador confirma la identidad del administrador.
5. Configura `APP_URL`, `MAIL_*` y `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` en `.env`. El callback es `/auth/google/callback`. Para enviar correos reales se necesita un transporte SMTP o proveedor configurado; `MAIL_MAILER=log` solo sirve para desarrollo.
6. Compila con `npm.cmd run build` en Windows o `npm run build` en otras plataformas.

Las credenciales reales permanecen fuera de Git. Las cuentas antiguas no verificadas deben verificar su correo; no existe una aprobación manual adicional para registro.

## Cambios y archivos

- `routes/web.php`, `AuthController`, `ActiveAccount`, `CheckRole` y `User`: registro por nombre de rol, elección en sesión, rutas guest, verificación de correo, sesiones regeneradas, cuenta activa y permisos en servidor.
- `PublicSearchController`, `VehicleSearch`, `welcome` y `public-result`: búsqueda real normalizada por VIN o placa, respuesta pública con identificador y estado, estadísticas reales, honeypot y límite de 10 consultas/minuto. No guarda consultas públicas en historiales personales.
- `VehicleController`, `TheftReport`, `vehicles/*`: propiedad de registros, transacciones de vehículo/reporte/auditoría, revisión administrativa y estados explícitos. Los reportes nuevos quedan pendientes incluso si un formulario solicita validarlos. Un cambio de fecha o descripción por registro requiere una nueva revisión.
- `ConsultationController`, `Consultation`, `consultations/*`: consultas autenticadas, historial propio incluso sin coincidencias, estados claros y análisis regional basado únicamente en reportes validados. Se conserva el mapa y se escapa su contenido.
- `UserController`, `Admin/RoleController`, `users/*`, `admin/roles`: asignación administrativa de roles, descripción de roles y activación/desactivación de cuentas. La acción de desactivar conserva vehículos e historial. Los nombres y permisos de los tres roles se mantienen definidos en el servidor.
- `DashboardController`, `layouts/*`, `dashboard/*`, `public/css/platform.css` y vistas de autenticación: estilo común, navegación por rol y accesible desde móvil, dashboard propio para registro y formularios actualizados.
- `AppServiceProvider`: paginación Bootstrap, requisitos de contraseña y límite de 30 operaciones de escritura/hora por cuenta con evento `suspicious_write_rate` en bitácora. Las rutas de vehículos tienen además un límite de 60 solicitudes/minuto.
- `routes/console.php`, seeders, `.env.example`, `.gitignore`: creación segura del administrador, variables de OAuth de ejemplo sin secretos y exclusión de archivos LSP generados.
- `vite.config.js`, `resources/css/app.css`: compilación coherente con Tailwind 3 y PostCSS ya instalados. No se agregaron dependencias.

### Migraciones nuevas

- `2026_10_18_000001_secure_accounts_and_reports.php`: agrega `users.is_active`, amplía estados de reportes, agrega `updated_by`, impide múltiples reportes actuales por vehículo y permite consultas con `vehicle_id` nulo y valor de búsqueda. Normaliza VIN/placa existentes; si detecta colisiones o reportes duplicados, se detiene antes de cambiar el esquema y exige resolverlos sin fusionar información automáticamente. Los reportes antiguos `activo` pasan a `pendiente_validacion`, pues no consta una validación. Sincroniza el estado del vehículo.
- `2026_10_18_000002_retire_legacy_demo_credentials.php`: rota únicamente contraseñas que coinciden con las antiguas credenciales conocidas de demostración o Google y desactiva el administrador de demostración afectado. Conserva una auditoría sin contraseñas. No afecta contraseñas distintas de las antiguas predeterminadas.

El rollback de estas migraciones no restaura contraseñas conocidas, no reactiva cuentas de demostración ni vuelve obligatorias referencias a vehículos eliminados. Conserva los estados ampliados para evitar pérdida de información.

## Permisos

| Rol | Acceso |
| --- | --- |
| admin | Usuarios y estado de cuenta, asignación y descripción de roles, todos los vehículos/reportes, validación/rechazo/resolución, consultas, región y bitácora. |
| consulta | Consulta por VIN o placa, análisis regional de reportes validados e historial propio. Sin escritura de vehículos/reportes. |
| registro | Crear vehículos/reportes, listar sus vehículos y editar/eliminar únicamente registros propios. Sin módulos de consulta autenticada. |

El login no elige roles. Google no cambia el rol de cuentas existentes y exige correo verificado por el proveedor, coincidencia de identidad y validación de estado OAuth mediante Socialite. Las cuentas nuevas reciben solamente el tipo público elegido previamente en sesión. Una cuenta sin rol o desactivada no puede entrar a los módulos.

Los reportes se publican inmediatamente como pendientes. La consulta pública nunca incluye nombre del registrador, correo, dirección, coordenadas ni descripción. `pendiente_validacion` no significa robo confirmado; `validado`, `rechazado` y `resuelto` son decisiones administrativas. La retirada conserva la instantánea en bitácora; eliminar un vehículo conserva la auditoría y las consultas pasan a una referencia nula.

## Validación

Pruebas en SQLite en memoria, sin modificar la base de trabajo: registro tradicional y OAuth simulado en ambos roles, verificación de correo, acceso inmediato, bloqueo de admin público, configuración faltante, propiedad, escalamiento de rol, cuenta desactivada, privacidad, búsquedas, historial, duplicados, límites, estados, retiro, auditoría, vistas administrativas y migración de datos anteriores.

Comandos de comprobación: `php artisan test --compact`, `npm.cmd run build`, `php artisan view:cache`, `php artisan route:list` y Laravel Pint sobre los archivos PHP cambiados. El resultado definitivo se informa en la entrega.

Resultado local del 18 de octubre de 2026: **43 pruebas aprobadas, 276 aserciones**; compilación Vite aprobada (58 módulos), compilación Blade y listado de rutas aprobados. Pint aplicado a los archivos PHP modificados. Se eliminó después la caché de vistas generada para la comprobación.

Pendiente de integración externa: OAuth real con credenciales del proyecto, entrega real de correo y ejecución de migraciones contra una copia de la base MySQL del entorno de destino. Las pruebas de Google usan Socialite simulado. No se ejecutaron migraciones sobre la base local de trabajo ni se publicó la aplicación. Bootstrap y los mapas conservan sus proveedores CDN existentes.

La revisión visual en navegador queda pendiente: la sesión no ofrece navegadores conectados. Las vistas se verificaron mediante respuestas HTTP de Laravel y compilación Blade.
