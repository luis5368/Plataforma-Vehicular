# Verificación de correo y acceso administrativo

## Diagnóstico local

El diagnóstico de configuración realizado sin consultar ni modificar usuarios encontró `mail.default=log`, sin host SMTP externo, usuario SMTP ni contraseña SMTP configurados, y sin caché de configuración. En ese modo Laravel registra el mensaje y su enlace; no lo entrega a una bandeja.

La pantalla mostraba literalmente `verification-link-sent` porque el controlador devolvía una clave de estado y el layout la imprimía sin traducir. Ahora se distingue entre envío al servicio de correo, registro local y generación en memoria. Si el transporte falla, se muestra un error en español y no se conserva un aviso de éxito anterior. El registro del fallo solo incluye identificador de usuario y tipo de excepción; no incluye el texto del error SMTP, credenciales ni enlaces firmados.

Un administrador no verificado debe seguir viendo «Verifica tu correo». Las rutas del panel conservan `auth`, `active`, `verified` y, en administración, `role:admin`. La navegación administrativa depende del nombre de rol guardado. No es necesario ni correcto quitar `verified`, editar usuarios o ejecutar SQL para solucionar la entrega de correo.

## SMTP real

Configura **solo en tu `.env` privado**, usando los valores proporcionados por tu proveedor:

```dotenv
APP_URL=http://localhost:8000
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=usuario_del_proveedor
MAIL_PASSWORD="credencial_del_proveedor"
MAIL_FROM_ADDRESS=notificaciones@example.com
MAIL_FROM_NAME="Plataforma Vehicular"
MAIL_TIMEOUT=15
```

Los dominios y credenciales anteriores son ejemplos, no una configuración funcional. `MAIL_FROM_ADDRESS` debe ser un remitente autorizado por el proveedor. Si exige una contraseña de aplicación o una credencial específica, úsala en `MAIL_PASSWORD`; esto no cambia la contraseña de ningún usuario de Plataforma Vehicular. No compartas `.env` ni registres credenciales en Git.

Esta versión usa `MAIL_SCHEME`, no `MAIL_ENCRYPTION`: para el puerto 587 usa `smtp` (STARTTLS automático cuando el servidor lo ofrece); para TLS implícito, normalmente puerto 465, usa `smtps`. Sigue los parámetros de tu proveedor y no desactives la validación de certificados. `MAIL_URL`, si ya existe, puede sustituir los parámetros individuales: elimínala de la configuración privada si es antigua y quieres usar los campos anteriores. `MAIL_EHLO_DOMAIN` es opcional y solo hace falta si tu proveedor exige un nombre específico.

`APP_URL` debe coincidir con la dirección, protocolo y puerto usados al abrir la aplicación. No reemplaces `localhost` por `127.0.0.1` en un enlace firmado ni modifiques sus parámetros. Para un entorno accesible desde otros equipos, configura su dirección HTTPS real.

Después de editar `.env`:

```powershell
php artisan config:clear
```

Reinicia el proceso que sirve la aplicación si sigue usando configuración antigua. El envío estándar de verificación es síncrono, no requiere un worker de cola. El mailer `failover` ya no oculta un fallo SMTP mediante un respaldo a `log`: un registro local no equivale a entrega real. Para este flujo configura directamente `MAIL_MAILER=smtp`.

Un mensaje de éxito significa que el transporte aceptó el envío, no que se haya confirmado su llegada a la bandeja. Revisa spam y los registros de entrega del proveedor si no aparece. No se ha probado SMTP real desde esta sesión: faltan credenciales válidas.

## Desarrollo con log

Si quieres comprobar el flujo local sin SMTP:

```dotenv
MAIL_MAILER=log
MAIL_LOG_CHANNEL=single
LOG_LEVEL=debug
```

Ejecuta `php artisan config:clear` y pulsa «Reenviar enlace» en la pantalla de verificación. La interfaz indicará que **no se envió a una bandeja**. Abre localmente `storage/logs/laravel.log` y busca el mensaje más reciente para la cuenta y su URL con `/verify-email/`, `expires=` y `signature=`. Puedes ver las últimas líneas con:

```powershell
Get-Content -LiteralPath storage/logs/laravel.log -Tail 200
```

No pegues ese contenido en un chat ni publiques el enlace: permite verificar la cuenta junto con la sesión autenticada. Copia la URL completa desde la parte de texto del mensaje y ábrela en el mismo navegador donde iniciaste sesión. Esto verifica el flujo local por el enlace estándar, sin marcar el correo manualmente en la base.

Si `MAIL_LOG_CHANNEL` está vacío, el correo usa el canal de logging predeterminado; con `daily`, el archivo incluye la fecha. El nivel debe permitir mensajes `debug`, que es el nivel usado por el transporte `log`. `MAIL_MAILER=array` solo conserva mensajes en memoria para pruebas y no permite recuperar un correo desde una bandeja o archivo.

## Pasos para la cuenta administradora existente

1. Configura SMTP y limpia la caché de configuración como se indicó. No ejecutes migraciones ni seeders para esta corrección.
2. Inicia sesión con el correo y la contraseña actuales de la cuenta existente. El rol `admin` y el estado activo no sustituyen la verificación de correo.
3. En «Verifica tu correo», pulsa **Reenviar enlace**. Si aparece un error de envío, corrige SMTP y reintenta; el límite se conserva en seis solicitudes por minuto.
4. Abre el correo recibido, revisa spam si hace falta, y pulsa **Verifica tu correo electrónico** en el mismo navegador y con la misma cuenta autenticada. El enlace vence a los 60 minutos por defecto. Si vence, solicita otro.
5. Tras abrir el enlace válido, la aplicación confirma la verificación y permite el panel. Cierra sesión y vuelve a iniciar sesión con las credenciales actuales para comprobar el recorrido solicitado.
6. En `/dashboard` deben aparecer los enlaces **Usuarios**, **Roles y permisos** y **Bitácora**. Sus rutas siguen protegidas en el servidor para usuarios verificados, activos y con rol `admin`.

No se alteraron datos, roles, correos ni contraseñas de usuarios existentes. No se generó ni se siguió un enlace para la cuenta real durante este trabajo. Las pruebas usan usuarios ficticios y SQLite en memoria.

## Validación de esta corrección

- `php artisan test --compact`: 55 pruebas aprobadas, 388 aserciones. Incluye bloqueo del administrador no verificado, verificación con URL firmada, rechazo de enlaces vencidos, regreso al panel después de cerrar sesión e ingresar de nuevo, navegación y autorización por rol, reenvío y límite de frecuencia, generación real de un mensaje mediante `LogTransport` con logger simulado, traducción del correo y manejo de fallos sin secretos ni éxito previo.
- `npm.cmd run build`: compilación Vite aprobada, 58 módulos.
- `php artisan route:list`: revisadas rutas de verificación, dashboard y administración; los middleware y límites se conservan.
- Laravel Pint aplicado a los archivos PHP cambiados; `git diff --check` sin errores.

SMTP real queda pendiente de configuración y comprobación por parte del operador. No se ejecutaron migraciones ni comandos de escritura sobre MySQL.
