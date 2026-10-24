# Auditoría y preparación para producción — fase 4

Fecha: **2026-10-20**, America/Guatemala. **No autoriza un despliegue.** Se trabajó en el proyecto actual; se conservaron las fases 1–3 y los cambios previos de `package.json` / `package-lock.json`. No se modificaron `.env`, permisos funcionales ni datos reales. No hay migraciones nuevas.

**Ampliación 2026-10-23:** fotografías privadas y una sesión activa por cuenta incorporan dos migraciones aditivas y requisitos de GD, cargas, limpieza y respaldo. La ausencia de migraciones indicada arriba corresponde a la auditoría inicial. Véase [Fotografías y sesión única](FOTOS_Y_SESION_UNICA.md); los resultados históricos de esta auditoría se conservan como tales.

## Dictamen y estados

**Preparación local aprobada con pendientes; salida a producción bloqueada hasta cerrar las comprobaciones de destino.** El destino previsto es **Hostinger, mediante un subdominio y conexión a MySQL**, según la ampliación solicitada para esta fase. El plan contratado, subdominio exacto, ubicación/proveedor de la base, sistema operativo, servidor HTTP y topología de proxy siguen pendientes. El apartado 11 contiene las comprobaciones específicas; no se presuponen cPanel, Apache, Nginx, contenedores ni servicios gestionados.

| Estado | Comprobación | Evidencia / condición de salida |
|---|---|---|
| Aprobado local | Regresiones y permisos de fases 1–3 | 101 pruebas Laravel, 913 aserciones; consulta detallada de solo lectura, publicación automática y borrado de reportes propios abiertos conservados |
| Aprobado local | MySQL y concurrencia | MySQL Community **8.0.44**, InnoDB, aislamiento **REPEATABLE-READ**; 20 comprobaciones del banco aislado, incluidas operaciones simultáneas |
| Aprobado local | Sesiones `database` | Tabla e índice por usuario creados por las migraciones; dos sesiones del usuario eliminadas por el servicio de desactivación en MySQL; vehículo conservado |
| Aprobado local | Cachés Laravel y frontend | Generación, arranque con caché y limpieza correctos; Vite y tres pruebas JavaScript correctos |
| Aprobado local con alcance limitado | Secretos en archivos rastreados | `.env` ignorado y no rastreado; escaneo heurístico de 178 archivos, sin patrones de credenciales SMTP/OAuth, claves de aplicación ni claves privadas; hallazgo heredado indicado abajo |
| Aprobado local | Dependencias de producción | Composer sin avisos ni abandonados; npm `--omit=dev` sin vulnerabilidades |
| Pendiente de credenciales/hosting | SMTP y OAuth reales | Configuración privada y pruebas controladas en staging; no se enviaron mensajes ni se contactó con Google para autenticar una cuenta real |
| Pendiente de credenciales/hosting | Dominio, TLS, cookies, proxy, permisos, logs, capacidad | Operador identifica destino y aporta evidencias indicadas en este checklist |
| Bloqueante de salida | Respaldo/restauración y ensayo de actualización | Falta copia autorizada de la base existente y ensayo con el motor/configuración de destino; la prueba sintética no sustituye ese ensayo |
| Bloqueante de salida | Aceptación de capacidad y seguridad del destino | Falta SLO de latencia/carga, prueba con concurrencia y topología de producción, y smoke test HTTPS completo |
| Pendiente de desarrollo | Cadena de Tailwind 3 | Ocho hallazgos npm solo en desarrollo: cinco altos y tres moderados; resolver o aceptar expresamente el riesgo del proceso de build antes de liberar |

Los estados «aprobado local» **no certifican la configuración del servidor de producción**. Cada pendiente del destino debe tener responsable, fecha y evidencia privada que no incluya secretos.

## 1. Prerrequisitos del destino

- [ ] Registrar proveedor/plan, dominio canónico, PHP y extensiones, versión exacta de MySQL, aislamiento/transacciones, servidor HTTP, proxy/balanceador, número de instancias, cuotas y mecanismo de publicación/rollback. Estos datos están **pendientes**.
- [ ] PHP compatible con `composer.lock` y requisitos de Composer (proyecto: PHP `^8.2`, Laravel 12); verificar `composer check-platform-reqs --no-dev` en el destino. No ejecutado contra el hosting.
- [ ] Separar staging, producción y base de pruebas, con usuarios y almacenes independientes. Las credenciales de pruebas nunca deben apuntar a producción.
- [ ] Congelar y revisar el artefacto exacto; no ejecutar `composer update`, `npm update`, `npm audit fix --force` ni `composer setup`. Este último incluye generación de clave y migraciones y no es un procedimiento de producción seguro.
- [ ] Mantener consulta con detalle de solo lectura; registro con publicación automática y eliminación solo de reportes propios abiertos; admin con sus controles. No añadir aprobación manual ni cambios de permisos para aprobar este checklist.

## 2. Entorno, HTTPS y sesiones

Revisar mediante aserciones de verdadero/falso o inspección privada. **No usar `config:show`, volcar configuración, imprimir `.env`, cookies, URLs firmadas ni tokens.** Se inspeccionaron nombres y valores por defecto del código, no la configuración privada instalada.

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con HTTPS y dominio canónico real. Mantener la clave existente en el gestor de secretos; no ejecutar `key:generate` en una instalación existente. La clave no debe estar en el artefacto público ni en salidas de CI.
- [ ] Redirección HTTP → HTTPS en el servidor/perímetro; certificado válido y renovación monitorizada. Probar que los enlaces de recuperación/verificación conservan HTTPS, host y firma.
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, `SESSION_PATH=/`; dominio de cookie restringido al ámbito necesario, sin ampliarlo a subdominios sin justificación. `lax` permite el retorno GET de OAuth con su estado de sesión. Ajustar `SESSION_LIFETIME` y cifrado del almacén según política.
- [ ] Usar **`SESSION_DRIVER=database`** para la revocación por usuario implementada. Confirmar `SESSION_CONNECTION` y `SESSION_TABLE` si se personalizan. La tabla debe tener `id`, `user_id` indexado, `ip_address`, `user_agent`, `payload`, `last_activity`; la migración inicial crea la tabla y el índice. No basta con que exista en el servidor local.
- [ ] En dos navegadores de staging: autenticar la misma cuenta de prueba, desactivarla desde Perfil o administración, comprobar logout del navegador actual, rechazo de ambos accesos protegidos y del login, reactivar desde admin y comprobar que ambos requieren login nuevo. Verificar auditoría sin contenido sensible. Localmente se probaron invalidación/CSRF por HTTP en la suite y eliminación de dos filas de sesión en MySQL; no se hizo este recorrido visual remoto.
- [ ] La ampliación de sesión única revoca el hash por cuenta en todos los drivers al desactivar/reactivar. Si el destino usa Redis, `file` u otro driver, comprobar esa revocación y login posterior desde dos navegadores antes de aprobar. `database` conserva además el borrado físico de filas. No se cambia el driver instalado automáticamente. Véase [Ciclo de cuentas](CICLO_CUENTAS.md).
- [ ] Confirmar almacén compartido de sesiones y caché para todas las instancias. El rate limiter usa la caché; `array` no es apropiado para producción. El valor por defecto de `CACHE_STORE` en código es `database`; verificar tablas `cache` y `cache_locks` o el almacén compartido elegido.

### Proxy HTTPS

`bootstrap/app.php` no declara proxies de confianza específicos. No se agregó confianza universal sin conocer el hosting.

- [ ] Si TLS termina directamente en PHP/servidor web, comprobar que Laravel recibe el esquema HTTPS correcto.
- [ ] Si termina en un proxy, obtener sus IP/CIDR y las cabeceras que garantiza. Configurar `trustProxies` de Laravel en `bootstrap/app.php` con **solo** esas redes y cabeceras; no confiar ciegamente en `*` ni en cabeceras suministradas por clientes directos. No hay una variable `TRUSTED_PROXIES` conectada por el proyecto: añadirla a `.env` por sí sola no configura nada.
- [ ] Probar solicitudes a través del proxy y directas: URLs HTTPS, IP fiable para auditoría/límites y rechazo de suplantación de `X-Forwarded-*`. Probar OAuth y enlaces firmados después del ajuste. Resultado pendiente de topología real.

## 3. Correo y OAuth

### Variables reconocidas

SMTP: `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_URL` (opcional, puede sobrescribir los campos individuales), `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_TIMEOUT`, `MAIL_EHLO_DOMAIN`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. Para el flujo real elegir `smtp`; esta versión usa `MAIL_SCHEME`, **no** `MAIL_ENCRYPTION`. `smtp`/STARTTLS o `smtps`/TLS implícito deben coincidir con los requisitos del proveedor y no desactivar la verificación del certificado. Los nombres están conectados en `config/mail.php`; `.env.example` contiene las variables básicas, no todas las opcionales.

Google: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, conectadas en `config/services.php`. El callback existente es **`/auth/google/callback`**, bajo el dominio HTTPS canónico exacto, idéntico al registrado en Google Console. La ruta de inicio es `/auth/google`. No se inventaron dominio, cliente ni credenciales. Se mantiene la validación de estado de Socialite y de identidad verificada.

- [ ] Confirmar remitente autorizado, DNS de entrega requerido por el proveedor (SPF/DKIM/DMARC), conectividad de salida y cuotas desde staging.
- [ ] Con autorización explícita del operador y una bandeja de prueba, registrar una cuenta, reenviar verificación, abrir el enlace firmado en la misma sesión, cerrar sesión e ingresar; probar recuperación de contraseña. No copiar enlaces ni cuerpos de mensajes en tickets o logs compartidos.
- [ ] Comprobar que error SMTP muestra error español sin éxito falso ni secretos. Aceptación por el transporte no garantiza llegada a bandeja; comprobar recepción y estado de entrega del proveedor.
- [ ] `MAIL_MAILER=log` no entrega a bandejas y registra enlaces sensibles localmente; `array` solo conserva mensajes en memoria. No aprobar correo de producción con esos transportes. No configurar respaldo silencioso de SMTP a `log`.
- [ ] En staging, probar Google para consulta y registro, retorno/cancelación/estado inválido, preservación del rol de cuenta existente y rechazo de cuenta desactivada. No usar una nueva cuenta para asignar privilegios admin.

La suite local usa fakes/transportes controlados y cubre fallos y autorización. **SMTP/OAuth reales están pendientes**. Véase [Correo](CORREO.md).

## 4. Servidor, artefacto y errores

- [ ] Document root **exclusivamente `public/`**. El árbol raíz, `.env`, `.git`, `vendor`, `storage` privado, dumps y cachés no deben servirse. No copiar `.env` dentro de `public/` ni resolverlo con permisos globales.
- [ ] Configurar el front controller `public/index.php`; en Apache existe `public/.htaccess` con rewrite y listados desactivados. Si el proveedor usa Nginx u otro servidor, preparar la configuración equivalente y probarla: `.htaccess` no se aplica allí.
- [ ] Solicitar rutas sensibles conocidas y conservar **solo códigos de estado**, no cuerpos ni cabeceras con cookies: deben dar 403/404. Verificar que las rutas inexistentes y errores 500/503 no exponen trazas, SQL o configuración; `display_errors=Off` en PHP y `APP_DEBUG=false`.
- [ ] Usuario del proceso PHP con lectura del código y escritura solo donde se requiere: `storage/` y `bootstrap/cache/`. Sin `777`, sin ejecución de subidas y sin permisos de escritura del servicio sobre todo el proyecto. ACL/propietario exactos pendientes del SO del hosting. Probar lectura/escritura con la identidad real del servicio, no solo con un administrador.
- [ ] Compilar en build con dependencias de desarrollo; servir `public/build/manifest.json` y los assets versionados de Vite. No publicar `public/hot`, servidor de Vite ni `node_modules`. Configuración actual: entradas `resources/css/app.css` y `resources/js/app.js`; `VITE_*` es público, nunca almacenar secretos allí.
- [ ] Instalar dependencias PHP desde lock con `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader` en el proceso aprobado; revisar scripts antes de ejecutarlos. No se ejecutó una reinstalación en esta fase.
- [ ] Logs fuera del document root, rotación/retención aprobadas, acceso restringido, espacio y alertas de 5xx monitorizados. El canal `daily` admite `LOG_DAILY_DAYS`; definir `LOG_CHANNEL`, `LOG_LEVEL` y retención expresamente. Evitar trazas de SMTP, cuerpos de peticiones, cabeceras de autorización, cookies, contraseñas y enlaces firmados; revisar también logs del proxy y query strings con VIN/placa.
- [ ] Excluir respuestas autenticadas y endpoints de consulta de cachés públicas/CDN. El endpoint del mapa devuelve `no-store`. `/up` prueba salud básica del framework, no certifica DB, SMTP ni procesos de cola.
- [ ] Aprobar conectividad, atribución y política de los proveedores existentes de Leaflet/CDN, mosaicos y geocodificación. No se sustituyeron proveedores ni se validó el mapa en un navegador real durante esta fase.

## 5. Secretos y alcance de revisión

Comprobaciones reproducibles sin imprimir coincidencias:

```powershell
git ls-files --error-unmatch .env  # Debe fallar: no rastreado. No imprime contenido.
git check-ignore .env             # Debe indicar que está ignorado.
node scripts/audit-tracked-secrets.cjs
```

Resultado del escaneo actual: 178 archivos rastreados. Único hallazgo (se informa solo archivo y tipo):

| Archivo | Tipo |
|---|---|
| `database/migrations/2026_10_18_000002_retire_legacy_demo_credentials.php` | Comparación de contraseñas heredadas codificadas |

No se alteró esa migración histórica. No hay `.env` real, archivos de clave privada ni artefactos de credenciales detectados entre los rastreados. `.env.example` está rastreado como plantilla; sus valores no se vuelcan. El escáner es heurístico, analiza el contenido actual de las rutas del índice y **no demuestra ausencia absoluta de secretos**, no revisa historia Git ni valida credenciales contra proveedores.

- [ ] Revisar historia, artefactos de CI, copias de respaldo, logs y publicaciones anteriores con una herramienta que redacte coincidencias. Si aparece una credencial real, revocarla/rotarla con su proveedor y tratar el historial según el procedimiento autorizado, sin reescribir Git automáticamente.
- [ ] Comprobar el artefacto final y la exposición HTTP del hosting. La revisión local de archivos no sustituye esa comprobación.

## 6. Migraciones, respaldo y rollback

No se consultó `migrate:status` contra la base real. Se ejecutaron todas las migraciones **solo** en MySQL aislado y en SQLite de la suite. No se añadió esquema ni índice nuevo.

- [ ] Capturar `php artisan migrate:status` en staging y luego mediante el procedimiento aprobado en destino, sin mostrar configuración privada. El estado requerido comprende todas las migraciones del repositorio hasta `2026_10_23_000002_create_report_photos`, incluida `2026_10_23_000001_add_active_session_hash_to_users`. Las nuevas columnas fuerzan reautenticación de las sesiones previas; coordinar esquema y código sin borrar usuarios/sesiones globalmente.
- [ ] Antes de actualizar, obtener un respaldo consistente y protegido de la base (InnoDB, sin DDL simultáneo), archivos persistentes y versión del artefacto; conservar acceso al gestor de secretos existente sin volcarlos al repositorio. Comprobar espacio, integridad del respaldo y restauración completa en una base separada.
- [ ] Ensayar sobre una **copia autorizada y protegida de la base existente** con MySQL de la versión/aislamiento/collation de destino. Los 10 000 registros sintéticos no sustituyen esta prueba de compatibilidad de datos.
- [ ] Verificar previamente duplicados tras normalizar VIN/placa y múltiples reportes por vehículo, con reportes de conteos o IDs tratados privadamente. La migración `000001` puede detenerse por duplicados; no fusionar ni eliminar automáticamente registros para continuar.
- [ ] Revisar efectos de datos: `000001` normaliza identificadores y cambia estados heredados; `000002` retira credenciales de demostración y puede desactivar cuentas específicas; `000003` separa validación/caso sin certificar pendientes o resueltos. Verificar que quede una cuenta administrativa activa con correo verificado y acceso válido, sin promover usuarios automáticamente.
- [ ] Planificar ventana y duración de ALTER/índices/cambios de FK. MySQL hace commits implícitos de DDL: un fallo puede dejar esquema parcialmente actualizado aunque la migración no figure como aplicada. Ensayar recuperación de ese caso.
- [ ] Después del ensayo: comprobar integridad referencial, unicidad VIN/placa/vehículo por reporte, conteos y atribución, tabla `sessions` con índice por usuario, auditoría, permisos y estado de migraciones. Probar flujos de cuenta y reportes sin nuevas aprobaciones manuales.
- [ ] Solo en una publicación futura autorizada, usar `php artisan migrate --force` tras respaldo, ensayo y revisión. **No ejecutado en la base real durante esta fase.** No usar `migrate:fresh`, `db:wipe`, seeders generales ni scripts de auditoría contra destino.
- [ ] Rollback: detener escrituras, conservar evidencias del fallo y decidir si basta volver al artefacto anterior con esquema compatible. Si no, restaurar respaldo completo a instancia separada, verificarlo y ejecutar el cambio de destino aprobado, contemplando pérdida/reconciliación de escrituras posteriores al respaldo. No prometer rollback sin pérdida mediante `migrate:rollback`: los `down()` eliminan metadatos y la retirada de credenciales no es reversible. Ensayar tiempos de restauración y fijar RPO/RTO antes de salir.

## 7. Preparación de cachés

En el destino autorizado, después de configurar secretos y permisos, los comandos previstos son:

```text
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Para limpiar o reconstruir bajo el procedimiento aprobado:

```text
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

No ejecutar `cache:clear` u `optimize:clear` indiscriminadamente contra almacenes compartidos. Los archivos de configuración compilada pueden contener secretos en una instalación real: no publicarlos ni adjuntarlos. No usar `env()` desde código de negocio para sustituir `config()` después de compilar.

**Ejecutado localmente:** `php tests/Production/cache-audit.php`. Ejecuta los seis comandos reales mediante el kernel de Artisan con un bootstrap temporal, directorio de entorno vacío, cachés aisladas y valores sintéticos. No carga `.env` ni incluye una clave de aplicación. Generó configuración/rutas/vistas, verificó el arranque de otra aplicación con las cachés y la ruta regional, y limpió todo. **8 comprobaciones aprobadas**. Las cachés normales del proyecto no se dejaron activadas. La prueba no acredita que las variables privadas de producción sean correctas.

## 8. MySQL, sesiones y concurrencia ejecutados

Banco reproducible: `tests/Production/run-mysql-audit.ps1`, `bootstrap.php`, `mysql-audit.php`, `mysql-worker.php`. Usa el código actual del workspace; no clona ni copia el proyecto. Se crean únicamente datos sintéticos. El runner arranca MySQL sin archivos de configuración externos, enlazado a loopback en **33079**, desactiva MySQL X y binlog y usa un directorio marcado bajo `storage/framework/production-audit/mysql-data`. Rechaza un puerto ocupado o un directorio existente sin marca; nunca toma credenciales de `.env`. Utiliza una instancia desechable local sin autenticación habilitada, **nunca apropiada para un servicio persistente o una red compartida**. La apaga al terminar. El directorio completo está excluido de Git.

```powershell
# Requiere PHP con pdo_mysql, proc_open y MySQL Server 8.0 instalado.
# Ajustar -MySqlBin a la instalación local; el valor por defecto es el usado en esta auditoría.
powershell -NoProfile -ExecutionPolicy Bypass -File tests/Production/run-mysql-audit.ps1
```

El script **borra/recrea exclusivamente la base `pv_audit` de esa instancia aislada** al ejecutar `migrate:fresh`. No adaptarlo para usar credenciales ni puertos de producción. Conserva resultados y planes sintéticos en `storage/framework/production-audit/mysql-results.json`. El servidor temporal quedó **apagado**.

Resultado: **20 comprobaciones aprobadas**. Dos procesos PHP independientes sincronizados por barrera, conexiones separadas y transacciones reales:

- MySQL 8.0.44 confirmado y migraciones completas correctas.
- Dos administradores intentan desactivarse simultáneamente: una operación aceptada y una rechazada; queda uno activo. Mismo resultado al degradarlos simultáneamente. Se usa `ActiveAdministratorGuard` real.
- Inserciones simultáneas con VIN repetido, placa repetida y reporte repetido por vehículo: una aceptada y una rechazada por la restricción MySQL en cada caso.
- Dos ediciones simultáneas mediante `VehicleController`: ambas aceptadas y cadena de auditoría consistente (el estado anterior de la segunda corresponde al nuevo de la primera).
- Desactivación mediante guard/servicio reales: elimina las dos sesiones en la tabla y conserva el vehículo.
- Ocho comprobaciones regionales de paginación/KPI, tope/omisiones y agrupación, detalladas a continuación.

Límites: es un ensayo de dos trabajadores por escenario, no una prueba prolongada de contención ni una certificación de otras versiones/aislamientos. Los permisos de cada ruta y el cierre HTTP se cubren adicionalmente con la suite Laravel; los procesos concurrentes invocan guard/controlador/servicio y restricciones SQL, no simulan un balanceador y dos navegadores completos.

## 9. Región y mapa: volumen, planes y latencia

Se añadieron **10 000 vehículos y reportes sintéticos** al fixture de concurrencia (total 10 001 vehículos). Distribución determinista en dos departamentos, diez municipios, cuatro zonas, coordenadas extendidas, casos resueltos/pendientes y 400 registros sin coordenadas. Sin importación de datos reales. Cada escenario tiene un calentamiento y **cinco muestras**; medidas con `hrtime` dentro de PHP sobre MySQL local, sin red HTTP, navegador ni render de Blade. Se mantuvieron **25 filas/página**, máximo **300 elementos**, agrupación por debajo de zoom 14 y límites de frecuencia existentes.

| Medida | Sin filtros | Departamento + municipio + zona |
|---|---:|---:|
| Total activo de tabla / KPI completo | 7 793 | 389 |
| Activos con coordenadas en el área | 7 480 | 389 |
| Elementos en zoom 14 | 300 | 300 |
| `truncated` en zoom 14 | true | true |
| Grupos en zoom 8 | 64 | 40 |
| Reportes representados en zoom 8 | 7 480, sin omisiones | 389, sin omisiones |
| Elementos en zoom 13 | 300, con omisiones | 300, con omisiones |
| JSON de puntos sin comprimir | 72 743 bytes | 73 099 bytes |
| Mediana del conjunto de operaciones | 682,95 ms | 203,90 ms |
| Máximo observado del conjunto | 709,55 ms | 209,29 ms |

Muestras sin filtros (ms): 669,52; 675,66; 682,95; 688,42; 709,55. Filtradas: 200,40; 202,88; 203,90; 204,10; 209,29. El conjunto medido incluye tabla + dashboard/KPI/distribuciones/tendencia + límites iniciales + tres consultas del mapa (zoom 14, 8 y 13). **No es latencia de un endpoint individual ni p95**, ni es comparable directamente con un servidor de producción. La tabla conserva los registros sin coordenadas; el mapa no los transforma en errores ni puntos falsos.

Se capturaron `EXPLAIN` y `SHOW INDEX` de las consultas efectivamente ejecutadas: 16 formas SQL por escenario, 12 sin filtros y 13 filtradas con al menos un acceso `ALL`. Se observaron `Using temporary` / `Using filesort` en ordenaciones y agrupaciones. La relación usa `theft_reports_vehicle_id_unique` y búsquedas `eq_ref` por PK; las consultas filtradas recorren `vehicles` y varias sin filtro recorren `theft_reports`. Índices relevantes existentes: únicos de VIN/placa, único de reporte por vehículo, FK de actores/propietario e índice `sessions.user_id`. No hay índices geográficos o de estado/fecha dedicados.

**Decisión:** no se crearon índices ni se modificaron límites. Los planes justifican medir candidatos compuestos para geografía/estado/fecha en una copia, pero esta distribución y cinco muestras no demuestran qué índice mejora la carga real. El límite acota la respuesta, no el coste de contar/agrupar. Antes de producción:

- [ ] Fijar volumen inicial/proyectado, selectividad y SLO de página/mapa, usuarios simultáneos y presupuesto de DB del hosting.
- [ ] Repetir con el motor/collation/configuración de destino y una distribución autorizada representativa; medir cada endpoint por separado, p50/p95/p99, consultas, filas examinadas, CPU, memoria y latencia de paginación profunda bajo concurrencia.
- [ ] Comparar planes antes/después de cualquier índice candidato en una copia, incluir coste de escritura y migración; aprobar solo con evidencia. No aplicar índices directamente por recomendaciones genéricas.
- [ ] En navegador, verificar filtros conservados, avisos de omisiones, agrupación, desplazamiento/zoom, límites 429 y errores 503 sin estados vacíos falsos. La suite Node cubre debounce, respuestas obsoletas, escape y fallos; falta inspección real en el hosting.

## 10. Dependencias y resultados finales

| Comando / validación | Resultado exacto |
|---|---|
| `composer audit --locked --format=json` | Exit 0; `advisories=[]`, `abandoned=[]` |
| `npm.cmd audit --omit=dev --json` | Exit 0; 0 vulnerabilidades |
| `npm.cmd audit --json` | Exit 1; 8 vulnerabilidades de desarrollo: 5 altas, 3 moderadas, 0 críticas |
| `php artisan test --compact` | 101 aprobadas, 913 aserciones, 40,25 s (SQLite en memoria; no sustituye el banco MySQL) |
| `node --test tests/js/regional-map.test.cjs` | 3 aprobadas, 0 fallidas |
| Runner MySQL aislado | 20 comprobaciones aprobadas; motor MySQL 8.0.44; servidor apagado al finalizar |
| `php tests/Production/cache-audit.php` | 8 comprobaciones aprobadas; cachés creadas, arranque comprobado y limpieza verificada |
| `npm.cmd run build` | Exit 0; Vite 7.3.6; 118 módulos; 1,82 s de build |
| Pint sobre `tests/Production` | Aplicado y `--test` aprobado |
| `node --check scripts/audit-tracked-secrets.cjs` | Exit 0 |
| `git diff --check` | Sin errores; avisos previos CRLF→LF en dos archivos de fases anteriores |
| Rutas regionales | `auth`, `active`, `verified`, `role:admin,consulta`, throttle independiente conservados |

Composer avisó que su caché global no era escribible y completó la auditoría sin caché. No se instalaron/actualizaron paquetes ni se modificaron los lockfiles.

### Hallazgos de desarrollo separados

El informe completo identifica `braces`, `chokidar`, `fast-glob`, `micromatch`, `tailwindcss` (altos) y `@tailwindcss/forms`, `postcss-nested`, `postcss-selector-parser` (moderados). Dos avisos raíz devueltos por npm: **GHSA-vfj7-8cjw-p6xm** (agotamiento de pila en patrones de braces) y **GHSA-rj75-hqrm-r3gf** (complejidad del parser de selectores). El resto representa propagación por dependencias; ocho paquetes afectados no significa ocho causas independientes.

El informe no ofrece solución automática compatible (`fixAvailable=false`). No se ejecutó `audit fix --force` ni se migró Tailwind. Estos paquetes se usan en build/desarrollo; no aparecen en la auditoría npm `--omit=dev`. Mantener builds aislados con código/entradas confiables y revisar una actualización/migración compatible en tarea separada; no afirmar que todo el árbol está libre de vulnerabilidades. La aceptación del riesgo del build sigue pendiente.

## 11. Hostinger: subdominio y conexión a MySQL

Este apartado es una preparación documental: **no configura Hostinger, DNS, certificados ni bases de datos**. Complementa los controles anteriores y no convierte las pruebas locales en una aprobación del hosting. Las acciones de esta lista se reservan al ensayo autorizado en staging y a una futura publicación aprobada por separado. No leer, adjuntar ni imprimir `.env`, credenciales, claves, tokens o volcados de configuración para aportar evidencias.

### 11.1 Identificar el plan y sus capacidades

Hostinger documenta que SSH y otras funciones dependen del nivel contratado; Web/Cloud no permiten configurar vhosts arbitrarios ni editar `httpd.conf`. En VPS la configuración depende del sistema/panel instalado. Confirmar las opciones de la cuenta, sin asumir que una guía de otro plan aplica. [Referencia oficial: acceso y funciones por plan](https://www.hostinger.com/support/which-file-transfer-and-server-access-options-are-supported-at-hostinger/).

| Dato a confirmar privadamente en hPanel/contrato o con soporte | Estado actual | Criterio verificable |
|---|---|---|
| Tipo y nombre exacto del plan: Web, Cloud o VPS | Pendiente | Identificación del servicio y límites aplicables, sin capturas con secretos |
| PHP web y PHP CLI | Pendiente | Ambos compatibles con `composer.lock` y PHP `^8.2`; comprobar que CLI/cron no usan otra versión |
| Extensiones y límites PHP | Pendiente | `composer check-platform-reqs --no-dev` correcto sobre el artefacto; PDO MySQL, OpenSSL, mbstring, XML/DOM, ctype, fileinfo, tokenizer, filter, session y demás requisitos realmente instalados; memoria/tiempo suficientes |
| SSH, Composer y ejecución de Artisan | Pendiente | Confirmar disponibilidad y permisos; `php -v`, `php -m`, `composer --version` y comprobación de plataforma desde el servidor, sin `phpinfo()` público ni `php -i` |
| Directorio privado y document root del subdominio | Pendiente | Existe una disposición soportada que expone únicamente el contenido de `public/` |
| Cron, procesos persistentes y comandos permitidos | Pendiente | Confirmación del plan de rutas/binarios, frecuencia mínima, tiempo de ejecución, límites y posibilidad de workers, si fueran necesarios |
| Conexiones salientes a MySQL y dirección de salida estable | Pendiente | Soporte confirma puerto/destino permitido y la IP de salida efectiva, o cada IP exacta si hay varias instancias |

- [ ] **Web/Cloud:** verificar las opciones efectivas de hPanel para PHP, SSH/Composer, subdominio, cron, symlinks y separación público/privado. No solicitar cambios de vhost propios de VPS como si estuvieran disponibles. Si Composer no está permitido, acordar un build externo compatible y comprobar en destino sus requisitos; si no existe un procedimiento seguro para Artisan/migraciones/cachés, mantener el despliegue bloqueado.
- [ ] **VPS:** identificar SO y panel, usuario de servicio, servidor web/PHP-FPM, firewall, actualizaciones, respaldos y responsable de operación. Confirmar privilegios de administración antes de planificar vhost, certificados o supervisor. No asumir que hPanel administra el MySQL instalado dentro del VPS.
- [ ] Revisar las tareas que necesita esta aplicación: la verificación de correo actual es síncrona; no inventar un requisito de worker para ella. Si se activa una cola o tareas programadas, ensayar cron/worker, reinicio y logs con las restricciones del plan. No usar el servidor de desarrollo de Laravel o Vite como servicio público.

### 11.2 DNS, HTTPS y document root del subdominio

Hostinger permite gestionar el subdominio como sitio independiente o dentro de otro sitio; en VPS depende del panel/SO. Con DNS externo, los registros se gestionan en el proveedor autoritativo. [Referencia oficial: subdominios y DNS](https://www.hostinger.com/support/1583405-how-to-create-and-delete-subdomains-in-hostinger/).

- [ ] Registrar el FQDN definitivo y quién gestiona sus DNS. Configurar el registro A con la dirección de entrada indicada para ese servicio; AAAA solo si IPv6 está habilitado y verificado. Usar CNAME únicamente si la modalidad contratada proporciona explícitamente ese destino. No confundir la IP de entrada del sitio con la IP de salida hacia MySQL.
- [ ] Verificar con `nslookup` o `dig` que el subdominio resuelve al destino esperado desde el servidor y una red externa, sin alterar los registros del dominio principal. Revisar registros contradictorios y propagación/TTL antes de aprobar.
- [ ] Emitir/habilitar el certificado para el subdominio, probar cadena y nombre válidos y confirmar renovación. Comprobar redirección HTTP → HTTPS, ausencia de contenido mixto y cookies seguras. Si hay proxy/CDN, aplicar los controles de proxies del apartado 2.
- [ ] **Opción preferida:** alojar el proyecto en un directorio privado y configurar el document root efectivo del subdominio a su carpeta `public/`, mediante una opción soportada por el plan/panel. En VPS revisar el vhost y el front controller; en Web/Cloud confirmar con hPanel/soporte la disposición permitida, sin inventar rutas absolutas.
- [ ] Si el plan obliga a una carpeta pública fija y no permite apuntar a `public/`, solo aceptar una disposición equivalente revisada: esa carpeta contendrá exclusivamente los archivos de `public/`; el código, `vendor`, configuración, almacenamiento privado y secretos estarán fuera de **todos** los directorios servidos. Las rutas del front controller deberán ajustarse y probarse en staging. Si no puede garantizarse esa separación, **bloqueante: elegir una modalidad compatible**, sin trasladar el proyecto completo al directorio público.
- [ ] Verificar también la exposición desde el dominio principal y otros subdominios: una carpeta privada respecto al subdominio no puede quedar accesible mediante otro sitio. No aceptar únicamente un rewrite hacia `public/` o una regla que oculte `.env` mientras el proyecto completo permanece público.
- [ ] Probar login, assets de Vite, rutas Laravel y errores 404. Para `.env`, `.git/config`, `composer.json`, `vendor/autoload.php` y rutas privadas de logs/dumps, conservar solo códigos 403/404; no descargar cuerpos potencialmente sensibles ni usar solicitudes con cookies reales en evidencias. Confirmar que no hay listado de directorios ni `public/hot`.
- [ ] Actualizar, solo en la configuración privada del futuro entorno autorizado, el origen HTTPS canónico y callback OAuth. Comprobar firmas de verificación y recuperación con el subdominio. Esto sigue pendiente y no se ejecutó durante la edición documental.

### 11.3 Determinar dónde está MySQL

**a) Base alojada en Hostinger**

- [ ] Identificar si se trata del servicio de bases gestionado en hPanel o de MySQL administrado dentro de un VPS. Registrar privadamente motor/versión, puerto, host, base asignada, límites y política TLS. Para el servicio gestionado, usar **el host y la configuración indicados en hPanel para esa base y ese origen de conexión**; no suponer `localhost`, la IP del sitio ni un hostname genérico. Para VPS, obtenerlos del administrador de ese servicio.
- [ ] Si Hostinger indica una conexión interna entre aplicación y base, comprobarla desde el alojamiento contratado. Si se requiere acceso remoto al servicio gestionado, el apartado **Remote MySQL** de hPanel autoriza la IP desde la que se inicia la conexión y muestra el hostname remoto; no configura las conexiones salientes hacia un proveedor externo. [Referencia oficial: Remote MySQL](https://www.hostinger.com/support/1583546-how-to-set-up-remote-mysql-access-in-hostinger/).
- [ ] Confirmar qué IP/origen debe autorizarse para la aplicación concreta. No abrir acceso desde Windows solo para que una prueba local pase; la evidencia necesaria es la conexión desde el entorno Hostinger.

**b) Base alojada en otro proveedor**

- [ ] Obtener del proveedor de MySQL el endpoint, puerto, motor/versión, CA/TLS, límites y procedimiento de allowlist. Obtener de Hostinger la **IP pública de salida hacia ese destino**, confirmada para el plan y ubicación contratados. Una IP resuelta desde el subdominio o una consulta desde Windows no sirve para inferirla.
- [ ] Contrastar la IP confirmada con el origen observado por el proveedor durante una prueba restringida. Considerar NAT, IPv6, PHP web frente a CLI y varias instancias: permitir únicamente las IP exactas confirmadas necesarias, sin ampliar a rangos para resolver diferencias. Definir cómo se actualizará la allowlist si Hostinger cambia de servidor/IP.
- [ ] Autorizar ese origen **en el firewall/allowlist y controles de usuario del proveedor externo**, y confirmar que el plan Hostinger permite tráfico saliente al puerto indicado. No buscar la solución en el Remote MySQL de hPanel: ese control protege bases alojadas allí.
- [ ] Si no se puede confirmar una salida estable o el plan bloquea la conexión, mantenerlo como **bloqueante** hasta acordar con ambos proveedores una salida fija o conectividad privada/túnel soportados y probados. No habilitar acceso global como solución temporal.

### 11.4 Red, privilegios y cifrado

- [ ] **No habilitar Any Host, `%`, `0.0.0.0/0` ni `::/0`. No exponer 3306 a todo Internet**, ni en Hostinger ni en el proveedor de MySQL. Un puerto diferente no sustituye la restricción por origen. Confirmar reglas mediante revisión del proveedor; una prueba desde un origen no permitido debe fallar, sin escanear servicios ajenos.
- [ ] Usar un usuario de aplicación exclusivo, restringido a la base de Plataforma-Vehicular y al origen autorizado. Otorgar solo las operaciones necesarias para ejecución normal, sesiones, caché y auditoría; sin privilegios globales, administración de usuarios, `FILE`, concesión de permisos ni root. Separar el usuario de migraciones, con permisos DDL limitados al esquema y al procedimiento autorizado. Confirmar qué granularidad admite el servicio; si no permite estos mínimos, registrar y resolver la limitación antes de aprobar.
- [ ] Separar credenciales/bases de staging y producción. No poner credenciales en argumentos, comandos pegados, historial del shell, tickets, repositorio o respuestas HTTP. No utilizar un script público de diagnóstico con contraseña embebida.
- [ ] Habilitar TLS cuando el servicio lo admita, con CA y verificación de identidad del servidor según su documentación. `config/database.php` reconoce `MYSQL_ATTR_SSL_CA`; confirmar que PDO MySQL y el proveedor negocian y validan TLS. Esa variable por sí sola no es evidencia de cifrado o validación correcta. No desactivar la comprobación del certificado para resolver errores.
- [ ] En la misma conexión de staging, comprobar `SHOW SESSION STATUS LIKE 'Ssl_cipher'`: el resultado debe indicar un cifrado activo cuando se usa TLS. No incluir credenciales en esa evidencia. Confirmar además la verificación del certificado/nombre con la configuración del cliente; un cifrado negociado no demuestra por sí solo autenticidad del servidor.
- [ ] Si el servicio no admite TLS, registrar la limitación. Para un trayecto por Internet, mantener bloqueada su aprobación hasta contar con transporte privado/cifrado soportado y probado; no enviar credenciales/datos en claro ni asumir que todos los planes incluyen túneles.

### 11.5 Prueba desde Hostinger y ensayo de migración/restauración

- [ ] Con autorización y credenciales **solo de staging**, probar desde el propio servidor Hostinger la resolución del endpoint, alcance del puerto, autenticación y un `SELECT 1` mediante PDO/Laravel. Repetir desde el proceso web si usa una red, versión PHP o usuario distintos de SSH/cron. No basta con la conexión desde Windows ni con que phpMyAdmin abra la base.
- [ ] Ejecutar el diagnóstico mediante una tarea privada/CLI autorizada o prueba web autenticada y temporal sin exposición de secretos; devolver únicamente éxito/fallo, versión del motor y estado TLS. Capturar excepciones sin imprimir mensajes de conexión, DSN, parámetros ni trazas; eliminar la herramienta temporal al terminar.
- [ ] Comprobar sesiones `database`, caché compartida y permisos de escritura/auditoría usando cuentas y registros de prueba de staging. En dos sesiones, verificar desactivación, rechazo y reactivación con login nuevo. Medir la latencia real Hostinger↔MySQL y repetir los escenarios regionales del apartado 9, incluidos filtros, agrupación y omisiones.
- [ ] Confirmar si el servicio es **MySQL o MariaDB** y registrar versión exacta, InnoDB, collation, SQL mode, aislamiento y límites. La aprobación local en MySQL 8.0.44 no acredita otra versión o MariaDB por compatibilidad nominal.
- [ ] Restaurar un respaldo autorizado en una base de ensayo aislada compatible con el destino. Desde el entorno Hostinger de staging, ensayar las migraciones con el procedimiento del apartado 6, registrar duración y efectos y verificar unicidad, atribución, auditoría, último administrador y conservación de cuentas/reportes. No usar el banco destructivo `pv_audit` ni `migrate:fresh` contra esa copia o el servicio real.
- [ ] Ensayar también la restauración/rollback del respaldo, su integridad, RPO/RTO y recuperación del artefacto. No aprobar por haber generado un dump; debe haberse restaurado y verificado. **No se ejecutaron estas acciones remotas ni migraciones de producción durante esta tarea.**

### 11.6 Pendientes y criterio de aprobación para Hostinger

| Pendiente del plan/proveedor | Evidencia requerida para cerrarlo |
|---|---|
| Plan, recursos, PHP/CLI/extensiones, SSH/Composer y tareas | Confirmación de la cuenta y comprobaciones de plataforma en Hostinger |
| Subdominio, DNS, HTTPS y disposición pública/privada | Resolución y certificado correctos, root limitado a `public/` o equivalente seguro, rutas privadas inaccesibles en todos los sitios |
| Ubicación/motor de MySQL y endpoint del servicio | Confirmación privada de hPanel o del proveedor de base; versión y parámetros compatibles |
| IP de salida y red permitida | Confirmación de Hostinger para ese plan y origen contrastado con el proveedor; acceso global deshabilitado |
| Usuario limitado y TLS/transporte seguro | Revisión de privilegios y conexión de staging cifrada/verificada desde el alojamiento |
| Migración, restauración y capacidad | Ensayo exitoso sobre copia autorizada compatible, restauración comprobada y SLO medido desde Hostinger |

**Aprobado en esta ampliación:** revisión documental y criterios preparados con referencias oficiales; no se certificó ninguna cuenta contratada. **Pendiente de plan/credenciales/proveedor:** todas las evidencias de la tabla. **Bloqueante para publicar:** cualquier falta de aislamiento público/privado, origen de red restringido, transporte seguro, compatibilidad o ensayo/restauración, además de los bloqueantes generales de este documento.

## Criterio final de salida

- [ ] Fotografías/sesión única: GD JPEG/PNG/WebP, límites de carga PHP/proxy, directorio privado persistente, scheduler de limpieza y respaldo/restauración **conjuntos de archivos y base** probados. No publicar `storage/app/report-photos`; confirmar la ruta autorizada y el rechazo de sesiones reemplazadas en fotos, administración y región. Estos requisitos están pendientes en Hostinger; no se editó su configuración.

- [ ] Todos los pendientes de hosting/credenciales tienen evidencia real y no quedan bloqueantes abiertos.
- [ ] Respaldo restaurado con éxito, ensayo de migración de la copia existente y rollback/RPO/RTO aprobados.
- [ ] Cookies/HTTPS/proxy y revocación multisesión probados en staging con la configuración de destino.
- [ ] SMTP/OAuth y flujos de los tres roles pasan smoke test sin secretos en salidas.
- [ ] Capacidad/SLO aceptados con volumen y concurrencia de destino; avisos de mapa y estados de error comprobados en navegador.
- [ ] Auditorías repetidas sobre el artefacto definitivo y hallazgos de desarrollo resueltos o aceptados expresamente.
- [ ] Operador autoriza por separado la publicación. **Esta fase no ejecuta commit, push, despliegue ni migraciones en una base real.**
