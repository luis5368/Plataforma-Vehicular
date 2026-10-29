# Fase 1: aceptación funcional antes de staging

> **Cierre registrado el 2026-10-25: FASE 1 APROBADA.** El usuario confirmó también que cerrar la sesión antigua de A no revoca B, que quitar una de dos imágenes antes de enviar guarda solo la restante, que los archivos inválidos/excesivos se rechazan sin guardar reporte ni foto y que marcador, popup, atribución y mapa regional se visualizaron correctamente. Estas confirmaciones resuelven los últimos pendientes de aceptación. La sección final contiene el estado vigente y prevalece sobre las matrices y planes históricos. No hay contraseñas ni claves temporales en este documento.

Fecha: **2026-10-25**, zona **America/Guatemala**.

Commit revisado: **`184ec9900867777531c4714951fa10a01c74b1a8`**.
Estado inicial: `git status --short` vacío; `git diff --stat` vacío. El trabajo anterior ya está incluido en el commit revisado. Se conservó íntegro. Esta revisión añade únicamente este documento; no modifica código funcional, paquetes ni configuración.

## Dictamen

**FASE 1 APROBADA Y LISTA PARA EL SIGUIENTE PASO DEL CRONOGRAMA: PREPARACIÓN DE STAGING.** Esta aprobación no constituye un despliegue ni certifica la configuración del hosting de destino.

La evidencia automatizada es satisfactoria: 138 pruebas Laravel, 1548 aserciones, 8 pruebas JavaScript, compilación Vite y comprobaciones de formato/cachés aprobadas. No se detectaron defectos funcionales reproducibles en esas comprobaciones. No equivale a una aprobación visual ni a una prueba de extremo a extremo con navegadores reales.

La revisión manual final del usuario permite aprobar la apariencia de las pantallas probadas, la revocación del acceso inicial al iniciar sesión con la misma cuenta en otro navegador, la carga correcta de fotografías y el despliegue correcto del mapa. También queda confirmado el diagnóstico HTTP de GD y JPEG/PNG/WebP, limitado al servidor aislado. No se atribuye esta revisión visual a Codex ni se presuponen capturas o pasos no comunicados.

El criterio de cierre existente exige completar los pendientes A–E y el plan manual. Las últimas confirmaciones del usuario resuelven los complementos que mantenían abierto el cierre: sesión antigua sin afectar a B, retirada de una selección antes del envío, rechazo de archivos inválidos/excesivos sin persistencia y visualización de marcador, popup, atribución y mapa regional. **No quedan requisitos obligatorios de esta fase pendientes ni bloqueos funcionales documentados**, considerando conjuntamente la evidencia automatizada y las confirmaciones manuales. No se inventan capturas, dispositivos, versiones de navegador ni ejecuciones adicionales. Véase el alcance de la sección final.

## Estados y alcance de la evidencia

- **APROBADO:** comprobación ejecutada y satisfactoria, dentro del alcance indicado.
- **FALLÓ:** comprobación ejecutada que no pasó.
- **BLOQUEADO:** no pudo ejecutarse por una dependencia o entorno ausente.
- **NO VERIFICADO:** no se ejecutó; no se infiere su resultado de otras pruebas.

Los resultados HTTP de Laravel y los DOM/Leaflet simulados en Node no son capturas ni inspecciones visuales. No se generaron imágenes de evidencia porque no hubo navegador disponible.

## Aislamiento comprobado antes de las pruebas

1. Se leyeron `phpunit.xml`, `tests/TestCase.php`, las pruebas de `tests/Feature`, `tests/Unit`, `tests/js`, rutas, componentes y servicios pertinentes. La suite predeterminada incluye Feature y Unit; **no** incluye `tests/Production/mysql-audit.php` ni sus workers. No se ejecutaron auditorías MySQL.
2. PHPUnit configura `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, sesión/caché en `array`, correo `array` y cola síncrona. `TestCase` usa un directorio de fixtures con `.env.absent` y genera una clave efímera; no carga el `.env` privado.
3. `bootstrap/cache` contenía solo `.gitignore`, `packages.php` y `services.php`; no había caché de configuración/rutas. Además se fijó una ruta de caché de configuración de prueba inexistente y se verificó que `.env.absent` no existiera.
4. Antes de PHPUnit se eliminaron del **proceso hijo** las variables heredadas cuyos nombres aparecen en `env(...)` de `config/*.php`, sin consultar/imprimir sus valores. Se fijaron explícitamente las variables de aislamiento del bloque siguiente. Esto no modifica archivos ni el entorno persistente del equipo.
5. Los usuarios provienen de factories y los vehículos/reportes de fixtures sintéticos. Las fotografías usan `Storage::fake('report_photos')` y archivos generados; no se accede al disco de fotos real. OAuth/correo se simulan en las pruebas correspondientes.
6. `RefreshDatabase` y algunas pruebas de migraciones crean/modifican únicamente el esquema efímero SQLite en memoria. **No se ejecutó `artisan migrate`, seeders ni ninguna migración u operación sobre una base real.** Las escrituras de fixtures no persisten al finalizar el proceso. Las pruebas sí generan archivos temporales y vistas compiladas locales.

## Comandos y resultados

| Comando/comprobación | Resultado observado |
| --- | --- |
| `git status --short`, `git rev-parse HEAD`, `git diff --stat` | Árbol inicialmente limpio; commit indicado arriba. |
| Lecturas de configuración/código con `Get-Content`, búsquedas con `rg`, listado de `bootstrap/cache` | Aislamiento y cobertura revisados; ningún `.env` privado leído. |
| `cua.getState()` | `apps=[]`, `browsers=[]`. Sin acceso a navegador para esta revisión. |
| `php -r "echo 'PHP CLI '.PHP_VERSION.PHP_EOL; echo 'GD CLI: '.(extension_loaded('gd') ? 'enabled' : 'missing').PHP_EOL;"` | PHP CLI **8.2.12**, GD CLI **enabled**. No demuestra GD web. |
| `php vendor/bin/phpunit`, después del saneamiento del proceso | **APROBADO: 138 pruebas, 1548 aserciones; 47,449 s, 64 MB.** Sin fallos ni omisiones. |
| `node --test tests/js/*.test.cjs` | **APROBADO: 8 pruebas; 0 fallos, 0 omitidas.** DOM, archivos y Leaflet simulados. |
| `npm.cmd run build` | **APROBADO: Vite 7.3.6, 121 módulos, 2,23 s.** Assets locales; no publicación. |
| `php vendor/bin/pint --test --dirty` | **APROBADO**, sin cambios PHP locales que formatear. No equivale a auditar el formato de todo el repositorio. |
| `php tests/Production/cache-audit.php` | **APROBADO: 9 comprobaciones**: config/route/view cache, configuración sintética, arranque con cachés, middleware y limpieza config/route/view. Usa directorios aislados, entorno ausente y correo/caché array; no base real. |
| `git diff --check` | **APROBADO** antes y después de redactar el documento. |

El sandbox no consiguió iniciar los dos primeros intentos de proceso (`helper_unknown_error: setup refresh had errors`). Los comandos necesarios se ejecutaron con la autorización solicitada para salir de ese sandbox, manteniendo el aislamiento de la aplicación descrito aquí. Fue un impedimento de la herramienta, no un fallo funcional del proyecto. Durante la inspección se corrigió una referencia de nombre de archivo al middleware real `SingleActiveSession.php`; no se cambió código por ello.

Preparación exacta del proceso de PHPUnit (PowerShell; ejecutar desde el repositorio, no cambiarla por un arranque normal de Artisan):

```powershell
foreach ($file in Get-ChildItem config -Filter *.php) {
    $content = [IO.File]::ReadAllText($file.FullName)
    foreach ($match in [regex]::Matches($content, 'env\([''"]([A-Z0-9_]+)[''"]')) {
        [Environment]::SetEnvironmentVariable($match.Groups[1].Value, $null, 'Process')
    }
}
$env:APP_ENV='testing'
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
$env:DB_URL=''
$env:SESSION_DRIVER='array'
$env:CACHE_STORE='array'
$env:MAIL_MAILER='array'
$env:QUEUE_CONNECTION='sync'
$env:APP_CONFIG_CACHE='storage/framework/testing-no-config-cache.php'
if (Test-Path $env:APP_CONFIG_CACHE) { throw 'Unexpected test configuration cache; refusing tests' }
if (Test-Path tests/Fixtures/environment/.env.absent) { throw 'Unexpected environment file; refusing tests' }
php vendor/bin/phpunit
```

## Matriz de aceptación inicial (histórica)

Los estados siguientes corresponden a la revisión inicial. Los resultados manuales posteriores y su alcance se registran en la sección final; no deben interpretarse como pendientes actuales cuando allí se haya confirmado su cumplimiento.

| ID | Comprobación | Estado | Evidencia y observaciones |
| --- | --- | --- | --- |
| A1 | Landing y módulos generan oscuro fijo, sin selector | APROBADO | `ThemeLayoutTest`: raíz `data-bs-theme="dark"`, ausencia del selector y del código de preferencias en páginas públicas/auth y pantallas de los tres roles. `app.css` carga `platform.css`; no hay implementación de temas en `resources`. |
| A2 | Preferencia anterior no se consume | APROBADO | Inspección de layouts/JS y búsqueda `localStorage`, `theme-toggle`, `data-theme=`, `prefers-color-scheme` sin resultados en `resources`; no se carga el antiguo script. Comprobación estática/HTTP, no navegación real con localStorage. |
| A3 | Diseño/contenido de landing y preferencia antigua en navegador | BLOQUEADO | No hubo navegador ni capturas. `welcome.blade.php` y estilos no se modificaron en esta revisión; esto no prueba la apariencia final. |
| A4 | Consulta pública por VIN y placa | APROBADO | `PublicReportSignalTest` recorre ambos campos para los seis estados de fixtures; `PlatformTest` cubre normalización, identificador inválido y ausencia de coincidencias. |
| A5 | Reporte vigente validado y abierto: rojo | APROBADO | Fixture `validation_status=validado`, `case_status=abierto`: `data-report-signal="danger"` y mensaje de reporte vigente. |
| A6 | Pendiente/no confirmado: amarillo | APROBADO | Fixtures pendiente y rechazado con caso abierto: señal `warning`, mensaje sin confirmar el hecho. |
| A7 | Caso resuelto y retirado: neutral | APROBADO | Fixtures resuelto y retirado; el estado del caso tiene prioridad sobre validación, incluido retirado pendiente. |
| A8 | Vehículo sin reporte: verde con aclaración | APROBADO | Señal `success`, texto que aclara que no garantiza que esté legalmente libre de robo. No equivale a certificación externa. |
| A9 | Sin coincidencia: neutral | APROBADO | `test_missing_vehicle_is_neutral_and_existing_registration_flow_stays_on_landing`. No se confunde con el verde. |
| A10 | Color, icono y texto; leyenda y rutas vigentes | APROBADO | Inspección del componente y CSS: rojo/amarillo/verde y neutral, iconos `exclamation-octagon`, `exclamation-triangle`, `check-circle`, `dash-circle`, `question-circle`, texto y `role=status`. Las pruebas HTTP comprueban señal/textos, leyenda exacta, enlace `login` y registro `register.choice`. Apariencia y visibilidad real de iconos quedan en A3. |
| A11 | Privacidad de resultado público | APROBADO | `PublicReportSignalTest`, `IndividualTheftMapTest`, `ReportPhotosTest`, `PlatformTest`: sin dirección privada, descripción, correo del autor, mapa, ruta de fotos o archivo privado. El controlador conserva su lista explícita de campos públicos y `Cache-Control: no-store`. Se conserva fuente/validación públicas existentes. |
| B1 | Consulta detallada con rol Consulta por VIN/placa | APROBADO | `IndividualTheftMapTest` usa ambos identificadores con fixtures; `AuthorizationPhaseOneTest` confirma detalle de solo lectura. |
| B2 | Coordenadas específicas y un marcador | APROBADO | Blade usa `vehicles.theft_latitude/theft_longitude` del esquema actual, no una ubicación general. Pruebas HTTP validan atributos; JS comprueba centro exacto, un marcador, zoom 15 y popup escapado. Leaflet 1.9.4/OSM compartido con regional, con atribución en `map-core.js`. |
| B3 | Coordenadas ausentes/parciales/inválidas | APROBADO | HTTP y JS: null, parcial, fuera de rango/NaN no inicializan mapa ni marcador. Mensaje exacto «No se registró una ubicación con coordenadas para este reporte». `(0,0)` se admite como coordenada válida, no se utiliza como reemplazo. El test JS prohíbe fetch/geocodificación del mapa individual. |
| B4 | Aviso de ubicación declarada y no acreditación del hecho | APROBADO | Componente y pruebas HTTP comprueban el aviso; JS comprueba mensaje equivalente. Sin filtración al público (A11). |
| B5 | Renderizado real de mapa, teselas, popup, atribución y formulario | BLOQUEADO | Sin navegador ni entorno HTTP aislado. Los mocks no prueban disponibilidad de CDN/OSM ni aspecto/interacción real. |
| C1 | Fotos permitidas y rechazo por tipo/tamaño/cantidad/dimensiones | APROBADO | `ReportPhotosTest`: JPEG/PNG/WebP válidos; SVG, GIF, contenido corrupto/falso, más de 5120 KiB, sexta imagen, lado >4096 px y >8 MP rechazados sin residuos. Máximo acumulado de 5 fotos también se aplica al editar. Se comprueba recodificación y eliminación de metadatos. |
| C2 | Vista previa y eliminación previa al envío, lógica | APROBADO | `report-photos.test.cjs`: selección crea previews; quitar un archivo actualiza el multipart y libera object URLs; selecciones inválidas bloquean envío. DOM/DataTransfer simulados. |
| C3 | Vista previa, selector nativo de archivos y envío en navegador | BLOQUEADO | No se ejecutó interacción real ni se capturó evidencia visual. |
| C4 | Fotografías privadas y autorización | APROBADO | Disco privado `storage/app/report-photos`, `serve=false`; ruta `reports.photos.show` (`/reportes/fotos/{photo}`) con auth, sesión actual, active, verified, rol y throttle. HTTP prueba propietario, Consulta/admin, denegación a otro registro/visitante, bloqueo de `/storage/...` y ausencia en público. No prueba reglas del servidor web real. |
| C5 | GD CLI | APROBADO | PHP CLI 8.2.12 con GD; suite de imágenes ejecutada sin omisiones. |
| C6 | GD del PHP web | BLOQUEADO | No se identificó ni consultó el SAPI que atiende HTTP. La comprobación CLI no demuestra Apache/FPM ni formatos habilitados en ese SAPI. |
| D1 | Reemplazo de sesión, rechazo de la antigua y supervivencia de la nueva, servidor | APROBADO | `SingleActiveSessionTest`: dos logins sintéticos, identificadores distintos, antigua redirigida al siguiente acceso, logout antiguo no borra vínculo nuevo y nueva conserva acceso. También cubre OAuth simulado, revocación, claves, cuentas distintas y stores separados. Los tokens no se imprimen. |
| D2 | Misma cuenta en dos perfiles/contextos de navegador independientes | BLOQUEADO | No hubo navegador. La prueba HTTP manipula sesiones simuladas y no se presenta como dos navegadores reales. No se utilizaron dos pestañas ni cuentas reales como sustituto. |
| E1 | Pantallas principales y permisos de admin/consulta/registro | APROBADO | `ThemeLayoutTest`, `AuthorizationPhaseOneTest`, `PlatformTest`, `RegionalPerformanceTest` y `Auth/EmailVerificationTest`: pantallas por rol, navegación autorizada y middleware. Consulta mantiene detalle de solo lectura; registro no entra en consulta ni opera registros ajenos; admin conserva controles. |
| E2 | Publicación automática, borrado propio abierto, último admin y desactivación | APROBADO | Suites `AutomaticReportsTest`, `AuthorizationPhaseOneTest`, `AccountLifecycleTest`, `ProfileTest`: publicación sin revisión manual obligatoria, casos cerrados/ajenos protegidos y último admin activo protegido. Ningún dato preexistente se usa o cambia. |
| E3 | Apariencia y navegación real de cada rol en escritorio/móvil | BLOQUEADO | Faltan navegador, cuentas sintéticas persistentes autorizadas y capturas; no se infiere aprobación visual de la raíz HTML oscura. |
| X1 | Concurrencia/locks e índices de MySQL, configuración y seguridad de staging/hosting | NO VERIFICADO | Fuera del alcance de esta aceptación. No se ejecutó la auditoría MySQL ni se preparó/desplegó staging. SQLite no certifica concurrencia MySQL. |

## Defectos y cambios de la revisión inicial (histórico)

No se encontraron defectos funcionales reproducibles en las comprobaciones ejecutadas; no hay correcciones ni refactorizaciones de código. La ausencia de navegador y la falta de verificación de PHP web son bloqueos de evidencia, no defectos declarados del producto. Las únicas diferencias rastreables de esta tarea deben ser este documento; la compilación regeneró assets locales ignorados por Git.

## Plan original para completar los bloqueos (histórico; resuelto según el cierre final)

### Prerrequisito común: entorno de aceptación autorizado

1. Confirmar con el responsable un entorno HTTP de prueba y una base desechable identificada expresamente. No reutilizar el `.env` del repositorio ni arrancar `artisan serve` contra su configuración sin confirmar destino. No emplear la base real como «prueba».
2. Preparar un arranque aislado que cargue únicamente configuración sintética y almacenamiento privado temporal; si usa base SQLite de archivo o MySQL de prueba, identificar la conexión antes de escribir. La SQLite `:memory:` de PHPUnit no es una base compartida entre peticiones de navegador.
3. Solo después de esa confirmación, preparar fixtures sintéticos separados para admin/consulta/registro, activos y verificados, más vehículos/reportes de los estados de A5–A9, con y sin coordenadas y fotos generadas. No copiar usuarios, correos, claves o vehículos reales. Las credenciales de fixtures se crean para ese entorno; no se adivinan credenciales existentes ni se registran en evidencia.
4. Conectar un navegador a la herramienta o realizar la revisión manual. Registrar versión, URL no sensible, tamaño de viewport y fecha en este documento; las capturas deben contener exclusivamente datos sintéticos y no mostrar cookies, claves ni herramientas de diagnóstico con secretos.

### Landing, temas, mapas, fotografías y roles

1. En el entorno aislado, antes de cargar la página establecer en el navegador la antigua preferencia `localStorage.setItem('infovehiculargt.theme', 'light')`. Recargar `/`, `/login` y pantallas de los tres roles: deben continuar oscuras y sin selector, también al navegar/volver atrás. Guardar capturas de landing y un módulo por rol, tanto en escritorio como en móvil. Verificar foco, lectura, navegación y que no hay controles tapados.
2. Consultar por VIN y por placa cada fixture de A5–A9, incluida una búsqueda inexistente. Capturar resultado público; comprobar color, icono y texto, advertencia del verde, leyenda, `Iniciar sesión` → ruta `login` y registro existente → `register.choice`. Confirmar que el resultado público no tiene mapa, fotos ni datos privados adicionales.
3. Entrar como Consulta de prueba y consultar VIN/placa del fixture con coordenadas: mapa centrado en el punto declarado, un marcador, dirección registrada, atribución visible, aviso de no acreditación y popup escapado. Repetir sin coordenadas y con coordenadas inválidas: mensaje de ausencia, sin punto por defecto. Revisar además el mapa regional y errores de proveedor sin confundirlos con «sin ubicación». Capturar mapa autenticado.
4. Como registro, capturar formulario y elegir fotos sintéticas JPEG/PNG/WebP. Quitar una selección antes de enviar; confirmar que ese archivo no llega al servidor. Probar los límites actuales de C1. Abrir una foto permitida a través de `/reportes/fotos/{photo}` y comprobar denegación sin sesión o con otro propietario; no ubicar archivos en `public` ni crear enlaces públicos al disco privado.
5. Revisar menús/rutas por rol: Consulta solo lectura detallada, registro solo vehículos/reportes propios y borrado propio abierto, administración solo admin. No ejecutar operaciones sobre entidades reales. Añadir evidencias y resultado observado, no solo una marca de aprobación.

### Sesión única: dos contextos reales

1. Usar dos perfiles distintos del navegador o dos contextos independientes sin cookies compartidas. Dos pestañas o dos ventanas del mismo perfil no cumplen esta prueba. Utilizar la **misma cuenta sintética** en ambos.
2. Iniciar sesión en A y abrir una ruta protegida. Iniciar sesión en B y confirmar acceso. Volver a A y solicitar de nuevo una ruta protegida: debe redirigir a login y mostrar sesión reemplazada. Volver a B y solicitar una ruta protegida: debe continuar activa. Registrar/capturar los resultados sin mostrar tokens ni cookies.
3. Repetir con sesiones nuevas A/B y solicitar cerrar sesión en A después del login de B; confirmar que B conserva acceso tras otra petición. Cubrir también el cierre desde la pantalla ya invalidada de A cuando corresponda. No cerrar sesiones de cuentas reales.

### GD en PHP web

1. Identificar el SAPI/configuración que atiende el entorno HTTP autorizado (Apache/FPM/etc.); no asumir que comparte `php.ini` con CLI.
2. Mediante un diagnóstico temporal restringido a ese entorno (fuera del repositorio y sin acceso público), obtener únicamente `PHP_SAPI`, `PHP_VERSION`, `extension_loaded('gd')` y la disponibilidad de decodificación/recodificación JPEG, PNG y WebP. No usar `phpinfo()` ni imprimir variables de entorno, claves o rutas sensibles. Retirar el diagnóstico al terminar.
3. Realizar una carga HTTP de cada formato sintético permitido y confirmar almacenamiento privado y lectura por ruta protegida. Registrar únicamente SAPI/versión, soporte de formatos y resultado. Si falta GD o un formato necesario, corregir la configuración de ese SAPI mediante el procedimiento autorizado y repetir antes de aprobar.

## Criterio de cierre y límites

Para cambiar el dictamen a listo para preparar staging, completar y registrar todos los bloqueos de A–E anteriores con evidencia del entorno aislado, sin fallos funcionales abiertos. No se requiere ni se autoriza desplegar durante esta fase. Los requisitos propios de staging/producción siguen sujetos a su checklist independiente.

No se leyeron/imprimieron secretos de `.env`, no se adivinaron credenciales, no se alteraron datos reales ni se ejecutaron migraciones/seeders sobre bases reales. No hubo commit, push, despliegue, correos reales ni cambios de sesiones de usuarios reales.

## Continuación: entorno de navegador preparado (2026-10-25)

### Versión conservada e inventario completo

- `git rev-parse HEAD`: **`184ec9900867777531c4714951fa10a01c74b1a8`**.
- `git status --short` al iniciar esta continuación: **`?? docs/VALIDACION_FASE_1.md`**. No se revirtió ni sustituyó ningún cambio previo. El documento preexistente continúa sin seguimiento; se amplió conservando el informe inicial.
- `phpunit.xml` incluye **Unit y Feature**: 1 archivo/clase Unit y 18 Feature (12 directamente en Feature y 6 en Feature/Auth). Los data providers generan varios casos; **138 es el número de casos ejecutados**, no de archivos ni métodos. Las 19 clases se detallan abajo.
- `tests/js` contiene exactamente tres archivos: `regional-map.test.cjs` (3 pruebas), `report-photos.test.cjs` (2) y `reported-theft-map.test.cjs` (3). **Total: 8**. No existe `theme.test.cjs` en esta versión.
- Se revisaron los cinco archivos de `tests/Production`: `bootstrap.php`, `mysql-audit.php`, `mysql-worker.php`, `run-mysql-audit.ps1` y `cache-audit.php`. Son arneses independientes, no forman parte de los 138 casos. Los cuatro primeros preparan/utilizan MySQL; **no se ejecutaron**. El auditor de cachés no se volvió a ejecutar en esta continuación porque su ubicación predeterminada no es el temporal nuevo; se compiló Blade mediante el arnés temporal. Las 9 comprobaciones de caché del informe inicial son evidencia histórica separada, no se suman a PHPUnit.
- El script Composer `test` ejecuta `artisan config:clear` y `artisan test`. **No se usó** porque ese arranque normal podría cargar configuración privada y limpiar cachés ajenas. `setup`, `dev` y los hooks de Composer tampoco se ejecutaron. Se utilizó PHPUnit directamente a través del wrapper temporal saneado. No se instalaron dependencias.
- El script npm `build` es `vite build`. Para impedir incluso la carga implícita de `.env` por Vite se ejecutó la API de Vite con la configuración existente y **`envDir` apuntando al directorio temporal vacío**, sin modificar `vite.config.js`.

### Conciliación de los resultados anteriores

La ejecución actual reproduce **138 casos / 1548 aserciones / 8 JavaScript**, igual que el informe inicial de esta versión local.

El registro anterior de **138 / 1644 / 17** correspondía al trabajo local con el sistema claro/oscuro todavía presente. El historial de esta conversación registra su retirada: desaparecieron las **9 pruebas JavaScript de tema**, por lo que 17 pasó a 8. `ThemeLayoutTest` conserva sus dos casos, pero ahora comprueba oscuro fijo sin selector. Actualmente visita **24 pantallas y realiza 120 aserciones**; antes verificaba además inicialización, preferencias y controles, con cuatro aserciones adicionales por pantalla. Esa sustitución explica la diferencia de **24 × 4 = 96**, es decir, 1644 − 96 = 1548, sin reducir el número total de casos.

Esta conciliación se basa en el estado actual verificable y en las modificaciones registradas en la conversación; **no se atribuye el resultado histórico a este mismo commit**. No se dispone de un commit identificado que contenga exactamente ese estado local anterior. `git log --all -- tests/js/theme.test.cjs` no identifica ese archivo en el historial disponible, y el padre `94595d1` no contenía `ThemeLayoutTest.php`. No se reconstruyó ni ejecutó una copia histórica distinta del proyecto.

Desglose del JUnit generado en el temporal en esta ejecución:

| Clase (prefijo Tests omitido) | Casos | Aserciones |
| --- | ---: | ---: |
| Unit\ExampleTest | 1 | 1 |
| Feature\AccountLifecycleTest | 3 | 51 |
| Feature\Auth\AuthenticationTest | 4 | 8 |
| Feature\Auth\EmailVerificationTest | 15 | 118 |
| Feature\Auth\PasswordConfirmationTest | 3 | 4 |
| Feature\Auth\PasswordResetTest | 4 | 8 |
| Feature\Auth\PasswordUpdateTest | 2 | 8 |
| Feature\Auth\RegistrationTest | 2 | 4 |
| Feature\AuthorizationPhaseOneTest | 16 | 129 |
| Feature\AutomaticReportsTest | 10 | 110 |
| Feature\ExampleTest | 1 | 1 |
| Feature\IndividualTheftMapTest | 10 | 59 |
| Feature\PlatformTest | 18 | 215 |
| Feature\ProfileTest | 5 | 21 |
| Feature\PublicReportSignalTest | 7 | 163 |
| Feature\RegionalPerformanceTest | 17 | 235 |
| Feature\ReportPhotosTest | 11 | 234 |
| Feature\SingleActiveSessionTest | 7 | 59 |
| Feature\ThemeLayoutTest | 2 | 120 |
| **Total** | **138** | **1548** |

### Aislamiento efectivo del servidor

Directorio nuevo fuera del repositorio, en adelante **TEMPORAL**:

```text
C:\Users\Usuario\AppData\Local\Temp\vscode-agent-codex-sandbox-tho9jF\pv-acceptance-942df6a741cd4e049ceebb29f8f1f409
```

Antes de crear el archivo se comprobó que ese destino no existía y no estaba dentro del repositorio; se mostraron solo las variables de conexión de prueba. Antes de migrar, un segundo preflight desde Laravel confirmó:

```text
DB_CONNECTION=sqlite
DB_DATABASE=TEMPORAL/acceptance.sqlite
PHOTO_STORAGE_TEMPORARY=verified
ONLY_SQLITE_CONNECTION=verified
CONFIG_CACHE_TEMPORARY=verified
```

En la salida real `DB_DATABASE` mostró la ruta absoluta completa anterior, no un alias. El archivo estaba vacío antes de migrar. La comprobación de almacenamiento validó el disco privado de fotos antes de preparar archivos. Se aplicaron **16 migraciones exclusivamente en esa SQLite**, sin usar seeders para crear el entorno del navegador.

- El wrapper `TEMPORAL/boot.php` requiere el bootstrap/código original, pero selecciona un directorio de entorno vacío, una clave aleatoria nueva y almacenamiento/bootstrap temporales. No se carga el `.env` del proyecto ni se ejecuta `public/index.php` con configuración normal.
- Antes del bootstrap se eliminan por nombre las variables heredadas de configuración sin leer/imprimir valores. Los ajustes temporales existen únicamente en el proceso y el directorio temporal. Ninguna clave ni contraseña se escribió en documentación, `.env` o archivos del repositorio.
- Laravel tiene **únicamente** la conexión SQLite temporal; se retiraron las conexiones MySQL/Redis del proceso. La sesión usa archivos temporales y una cookie propia; la caché usa archivos temporales para conservar límites entre peticiones; correo `array`, cola `sync`, logs `null`, servicios externos sin configurar y debug desactivado.
- Todos los destinos de caché config/rutas/servicios/paquetes/eventos y vistas compiladas están bajo TEMPORAL. No se limpiaron ni sobrescribieron cachés de otro entorno. Durante el preflight apareció una incompatibilidad de resolución de rutas absolutas Windows; se corrigió **solo en el wrapper temporal** mediante el método existente `addAbsoluteCachePathPrefix`. No se modificó Laravel ni código del proyecto y no se migró mientras el preflight fallaba.
- Fotos: `TEMPORAL/storage/app/report-photos`, privado, `serve=false`; los otros discos locales también están bajo TEMPORAL y no hay discos remotos ni enlaces de almacenamiento. Los archivos de subida PHP usan `TEMPORAL/uploads`.
- Router temporal con document root vacío y whitelist de assets `/build/`; no sirve archivos del proyecto, `.env`, SQLite, claves o fotos por acceso estático. Las fotografías del reporte siguen únicamente detrás de su ruta autenticada original.
- **URL: `http://127.0.0.1:8127`**. Se verificó listener **solo `127.0.0.1`**, PID inicial **13708**, y landing HTTP 200 con raíz oscura. El proceso se deja activo; su supervivencia depende de que no se cierre el proceso/equipo ni se elimine el temporal. No se instaló un servicio persistente.
- OAuth responde 503 desde el router temporal. Una CSP `self` bloquea scripts, imágenes, fuentes y conexiones externas del navegador; el servidor tampoco configura SMTP/Google/servicios remotos. No se contactó Hostinger ni MySQL.

**Limitación cartográfica histórica al preparar el entorno:** el repositorio utiliza Leaflet desde `unpkg.com` y teselas OSM externas; no se encontraron copias locales de Leaflet. La CSP temporal impedía cargar esas dependencias y entonces no permitía aprobar la visualización completa. Codex no descargó paquetes, teselas ni bibliotecas ni cambió código funcional. **El bloqueo de aceptación quedó posteriormente resuelto por las confirmaciones manuales del usuario sobre mapa, marcador, popup, atribución y mapa regional.** No se conoce ni se inventa cómo se habilitaron los recursos en esa revisión; no se presenta como un cambio de CSP realizado o comprobado por Codex. La dependencia de recursos externos deberá considerarse al preparar staging, sin mantener este bloqueo ya resuelto de fase 1.

### Resultados de esta continuación

Los wrappers mencionados existen exclusivamente bajo TEMPORAL, no son archivos nuevos del repositorio.

| Comando/comprobación | Resultado y alcance |
| --- | --- |
| `php TEMPORAL/preflight.php` | Conexión, archivo nuevo, fotos y cachés temporales confirmados antes de migrar. |
| `php TEMPORAL/prepare.php` | 16 migraciones en SQLite temporal; 4 usuarios verificados, 6 vehículos, 5 reportes y 1 foto sintéticos. No seeders del navegador. |
| `php -d upload_tmp_dir=TEMPORAL/uploads -d upload_max_filesize=8M -d post_max_size=32M -S 127.0.0.1:8127 -t TEMPORAL/public TEMPORAL/router.php` | Servidor oculto en background, activo. Las opciones elevan el límite de transporte para poder probar el rechazo funcional >5 MB; no cambian los límites de Laravel. |
| `php TEMPORAL/run-tests.php --log-junit TEMPORAL/phpunit.xml` | **138 casos, 1548 aserciones, 0 fallos/omisiones; 20,604 s, 64 MB.** Suite completa actual Unit/Feature, SQLite **en memoria separada de la base del navegador**, almacenamiento/vistas temporales. |
| `node --test tests/js/*.test.cjs` | **8 aprobadas, 0 fallos/omisiones.** No navegador. |
| `node TEMPORAL/build.mjs` | Vite **7.3.6**, **121 módulos**, **1,41 s**, con envDir temporal vacío. Mismo bundle oscuro; sin cambios rastreados de código/configuración. |
| GET loopback `/__acceptance/health` | HTTP JSON del arnés: SQLite y disco temporal correctos, **SAPI `cli-server`, GD=true, JPEG/PNG/WebP=true**. No imprime configuración privada ni claves. Esto corresponde al PHP web de este servidor temporal, no a Apache/FPM/hosting. Evidencia técnica observada; no es aprobación manual de cargas. |
| GET loopback `/` | HTTP 200, raíz oscura y CSP restrictiva presentes. No inspección visual. |
| `php TEMPORAL/verify.php` | Blade compilado en TEMPORAL; fixtures siguen en **4/6/5/1**, un archivo privado. No altera el estado de los casos. |
| `php vendor/bin/pint --test --dirty`, `git diff --check` | Satisfactorios. No hay archivos PHP locales modificados. |

**Precisión sobre seeders de la suite:** `tests/Feature/Auth/RegistrationTest.php` invoca internamente `RoleSeeder` en sus dos pruebas existentes. La suite completa lo ejecutó **solo en SQLite en memoria**, nunca en la base del navegador ni en una base real. No se ejecutó `db:seed`, `DatabaseSeeder` ni seeders para preparar el servidor. Esta excepción interna se explicita para no afirmar que la suite no ejecuta ningún seeder; no se alteró ni omitió la prueba para ocultarlo.

### Datos sintéticos y archivos para la prueba manual

Las cuatro cuentas verificadas corresponden a admin, consulta, registro y una cuenta consulta separada para sesión única. Las credenciales aleatorias se entregan únicamente en la respuesta al usuario; no se guardan aquí. Todos los vehículos pertenecen al registro sintético, no hay entidades reales.

| VIN | Placa | Resultado público esperado | Consulta detallada |
| --- | --- | --- | --- |
| `1HGCM82633A000001` | `P701TST` | Rojo: validado/abierto | Coordenadas del robo 14.6349, -90.5069 y una foto generada. |
| `1HGCM82633A000002` | `P702TST` | Amarillo: pendiente/abierto | Sin coordenadas. |
| `1HGCM82633A000003` | `P703TST` | Neutral: resuelto | Con coordenadas sintéticas. |
| `1HGCM82633A000004` | `P704TST` | Neutral: retirado | Sin coordenadas. |
| `1HGCM82633A000005` | `P705TST` | Verde: sin reporte | Sin reporte/mapa de robo; no certifica situación legal. |
| `1HGCM82633A000006` | `P706TST` | Rojo: validado/abierto | Mensaje exacto de ausencia de coordenadas. |
| `1HGCM82633A999999` | `P799TST` | Neutral: sin coincidencias | No se creó esa entidad. |

`TEMPORAL/uploads` contiene `permitida.png`, `permitida.jpg`, `permitida.webp`, `invalido.svg` y `excede-5MB.jpg` (6 MiB de contenido falso). Son archivos generados, no fotografías de personas/vehículos reales. Para crear un vehículo nuevo por HTTP se pueden usar **VIN `1HGCM82633A000007`, placa `P707TST`**, aún no creados al entregar el entorno.

### Plan manual original (histórico; resultados confirmados en el cierre)

Estado al preparar el entorno, previo a la confirmación manual: A3, B5, C3, D2 y E3 estaban pendientes. C6 contaba con diagnóstico HTTP del PHP temporal, sin certificar la carga manual ni otro SAPI. Este plan se conserva como referencia de pasos propuestos, no como constancia de su ejecución. La sección final actualiza los resultados confirmados por el usuario.

1. **Público/oscuro:** abrir la URL local. Si había preferencia clara, establecer la clave antigua en `light` en ese origen y recargar; no debe aparecer selector ni activarse el claro. Capturar landing y resultados de cada fila por VIN y por placa. Hacer como máximo 10 consultas públicas por minuto para no confundir el límite existente con un defecto. Comprobar icono/texto/color, leyenda y enlace de login. No debe haber fotos/mapa/detalle privado en público.
2. **Consulta:** entrar con la cuenta sintética Consulta normal, abrir `/consultar`, buscar los fixtures 1 y 6 y revisar fotos, aviso de ubicación declarada y ausencia de coordenadas. La coordenada válida se conserva en el HTML, pero el mapa debe informar la falta del proveedor con la CSP actual. No aprobar la cartografía como verificada. Revisar `/consulta-region` y `/historial`.
3. **Fotografías por HTTP:** entrar como registro; en `/vehiculos/create` completar el vehículo nuevo sugerido, marcar reporte, poner fecha pasada y descripción sintética. Seleccionar `permitida.png` y `permitida.jpg`; quitar una antes de enviar y comprobar que solo se guarda la restante. Repetir en un formulario sin enviar con `invalido.svg` (usar «todos los archivos» en el selector si es necesario) y `excede-5MB.jpg`: deben rechazarse claramente; no deben crear registros/fotos. Abrir la foto guardada por la ruta protegida y comprobar denegación sin sesión. Registrar estado observado y captura; no usar archivos personales. El PNG inicial del fixture fue adjuntado con el servicio del servidor desde CLI, **no constituye una carga HTTP probada**.
4. **Sesión única:** usar Chrome/Edge con perfiles distintos o contextos independientes; no dos pestañas ni dos ventanas que compartan perfil/cookies. En A entrar con la cuenta sintética reservada para sesiones y abrir `/dashboard`. En B entrar con **la misma cuenta**; B debe funcionar. Al volver a A, hacer una nueva solicitud protegida: debe mostrar sesión reemplazada y login. B debe conservar acceso al recargar. Repetir con logins nuevos A/B y, después del login B, cerrar sesión desde A; B debe seguir activa. Guardar resultados sin capturar cookies/tokens/contraseñas. No usar la cuenta de Consulta normal para esta prueba, así se evita interrumpir la revisión de mapas/detalle.
5. **Pantallas visuales:** landing, login, resultado público, dashboard de cada rol, detalle autenticado con/sin coordenadas y galería, formulario de vehículo, análisis regional, Perfil y un módulo administrativo (usuarios/roles/bitácora). Revisar escritorio y móvil, foco/teclado, textos, errores y ausencia del selector. No entrar en OAuth: está desactivado en este arnés.
6. Comunicar el resultado de cada paso como pasó/falló/bloqueado, con la pantalla y acción exactas. Este era el requisito de comunicación previo al cierre; las confirmaciones recibidas y el dictamen aprobado se registran en la sección siguiente.

El servidor se deja activo para esta revisión. No se hizo commit, push ni despliegue. No se consultaron/alteraron entidades reales, no se leyó `.env`, no se conectó a MySQL, Hostinger, SMTP u OAuth y no se modificó código funcional. Las únicas migraciones persistentes fueron las autorizadas en la SQLite nueva temporal; las de PHPUnit fueron efímeras y separadas.

## Resultados manuales confirmados por el usuario y dictamen vigente

Fecha de registro: **2026-10-25**. Fuente: confirmaciones del usuario posteriores a la creación y preparación documentadas, incluida su última comunicación explícita de cierre de sesión antigua, selección y rechazo de fotografías y visualización cartográfica. No se proporcionó una fecha/hora exacta de ejecución manual, inventario de pantallas/dispositivos, versiones de navegador ni capturas; no se inventan. A/B son los identificadores usados por el usuario, sin atribuirles marcas o dispositivos. Codex no realizó una nueva inspección visual ni repitió las pruebas en esta actualización documental.

Versión del repositorio al actualizar: `184ec9900867777531c4714951fa10a01c74b1a8`. Estado previo: únicamente `?? docs/VALIDACION_FASE_1.md`; se conservó ese documento y se modificó solo su contenido.

| Comprobación | Estado vigente | Evidencia y alcance exactos |
| --- | --- | --- |
| Revisión visual de las pantallas revisadas (aspecto visual de A3/E3) | APROBADO, confirmación manual del usuario | El usuario informa que las pantallas revisadas se muestran correctamente. No se extiende esta confirmación a todas las pantallas, móviles, teclado, preferencia antigua, navegación o capturas del plan previo si no fueron expresamente comunicados. |
| Revocación del acceso inicial al entrar con la misma cuenta en otro navegador (D2, escenario de reemplazo) | APROBADO, confirmación manual del usuario | El usuario confirma que el segundo inicio de sesión revoca el acceso inicial. Se registra otro navegador, sin inventar nombres/versiones ni pasos adicionales. |
| Cerrar la sesión antigua sin invalidar la nueva, comprobación manual complementaria | APROBADO, confirmación manual del usuario | Después de iniciar sesión en el navegador B, cerrar la sesión antigua desde A no revocó el acceso de B. Resuelve el pendiente manual de sesión única; no se inventan solicitudes, pantallas ni pasos adicionales. |
| GD y soporte JPEG, PNG y WebP por HTTP (C6) | APROBADO, diagnóstico HTTP confirmado por el usuario | El usuario confirma el diagnóstico del servidor aislado; la evidencia previa identifica SAPI `cli-server`. Esto demuestra soporte en ese servidor temporal, no en Apache/FPM ni hosting. No se deduce que se haya cargado cada formato por separado. |
| Despliegue y visualización cartográfica (B5) | APROBADO, confirmación manual del usuario | A la confirmación previa de despliegue correcto se añade que marcador, popup, atribución y mapa regional se visualizaron correctamente. Resuelve el pendiente cartográfico del plan. No se inventan coordenadas comprobadas manualmente ni cómo se resolvió el acceso a recursos externos; las pruebas automatizadas de coordenadas conservan su alcance. |
| Carga de fotografías por HTTP (C3, carga) | APROBADO, confirmación manual del usuario | El usuario confirma que la carga de fotografías se realizó correctamente. No se inventan cantidad, formatos, tamaños, archivos, rutas visitadas ni capturas. |
| Retirada de una selección antes del envío (complemento C3) | APROBADO, confirmación manual del usuario | Al seleccionar dos imágenes válidas, quitar una y enviar, solo se guardó la restante. No se inventan formatos, tamaños o archivos utilizados. |
| Rechazo de inválidas/exceso de límites sin persistencia (complemento C3) | APROBADO, confirmación manual del usuario | Los archivos inválidos y los que excedían el límite fueron rechazados sin guardar el reporte ni la foto. Resuelve el pendiente de rechazo y ausencia de persistencia. C1/C2/C4 mantienen además su evidencia automatizada histórica. |
| Capturas y detalle por pantalla/dispositivo | NO VERIFICADO | No se aportaron archivos ni un inventario. La confirmación visual del usuario se acepta como evidencia declarativa; no se afirma que existan capturas ni se convierte su ausencia por sí sola en un fallo funcional. |

### Cierre de pendientes y alcance de la aprobación

1. **Fotografías C3: resuelto.** La carga, la selección retirada y el rechazo sin guardar reporte/foto cuentan con confirmación manual explícita del usuario.
2. **Sesión única D2 y complemento: resuelto.** Se confirmó tanto la revocación inicial desde otro navegador como la conservación de B al cerrar A.
3. **Mapa B5: resuelto.** Se confirmaron despliegue, marcador, popup, atribución y mapa regional. No se mantiene el bloqueo histórico por recursos externos como impedimento actual de aceptación.
4. **Sin criterios obligatorios pendientes de fase 1.** La matriz automática y las confirmaciones manuales se complementan sin convertir cada aserción automática en una ejecución manual. No se declaran probados dispositivos, capturas o variantes que no se comunicaron. La ausencia de capturas permanece como limitación del registro de evidencia, no como fallo funcional ni bloqueo de cierre.
5. **Fuera de alcance:** MySQL/concurrencia y requisitos de configuración/seguridad del hosting o staging (X1) siguen sujetos a sus comprobaciones específicas del siguiente paso. La aprobación del servidor aislado no certifica esos entornos ni autoriza un despliegue. No son nuevos pendientes obligatorios de esta fase.

**Dictamen final: FASE 1 APROBADA Y LISTA PARA EL SIGUIENTE PASO DEL CRONOGRAMA (PREPARACIÓN DE STAGING).** Las confirmaciones manuales del usuario resuelven los últimos pendientes de sesión única, fotografías y mapa; se conservan las pruebas automatizadas satisfactorias registradas. No quedan bloqueos funcionales ni criterios obligatorios pendientes de esta aceptación y no se requiere una excepción de alcance. No se han comunicado fallos funcionales nuevos. Preparar staging es el siguiente paso, no una acción ejecutada por esta actualización documental.

### Cambios y verificación de esta actualización documental

- Único archivo modificado por esta tarea: **`docs/VALIDACION_FASE_1.md`**, ya existente y sin seguimiento al iniciar. Se actualizaron el dictamen/resumen, la identificación de evidencia histórica y esta sección de confirmaciones del usuario.
- Se ejecutó **`git diff --check`**: **salida vacía, código 0**. Comprobación adicional: **`git diff --no-index --check -- NUL docs/VALIDACION_FASE_1.md`**, **salida vacía, código 1**; `--no-index` informa diferencias entre el origen vacío y el documento, sin diagnósticos de espacios. Esta comprobación adicional incluye el documento porque el comando normal no incorpora archivos sin seguimiento.
- No se repitieron suites Laravel/JavaScript, compilaciones, diagnósticos HTTP ni operaciones de navegador: la modificación es únicamente documental. Los resultados y conteos anteriores se conservan como evidencia histórica.
- No se modificaron código funcional, configuración, dependencias, base de datos ni archivos de entorno. No se ejecutaron migraciones, seeders, commit, push ni despliegue en esta tarea.
