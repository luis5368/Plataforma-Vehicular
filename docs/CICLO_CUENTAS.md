# Ciclo de cuentas y conservación

El cierre normal desde Perfil desactiva la cuenta (`is_active = false`). Requiere la contraseña actual y conserva usuario, vehículos, reportes, fotografías privadas, consultas, bitácora y atribución. No ejecuta `User::delete()` ni activa las cascadas existentes del esquema. Las restricciones para eliminar reportes propios abiertos son independientes del cierre de cuenta.

La operación transaccional conserva la protección del último administrador activo. Cierra la sesión actual, invalida su identificador y token CSRF y revoca el token de acceso recordado. Con el controlador de sesiones `database` (predeterminado del proyecto), elimina las sesiones de ese usuario usando la conexión y tabla configuradas, tanto al desactivar como al reactivar. No se han añadido tokens API.

Las cuentas inactivas no pueden iniciar sesión mediante contraseña ni Google ni usar las rutas protegidas, incluidas las de verificación de correo. Cerrar sesión sigue disponible. Reactivar no cambia la contraseña ni verifica el correo: administración debe editar el usuario y activar su cuenta; después debe iniciar sesión nuevamente. Perfil no acepta cambios de estado.

La bitácora registra `deactivate_account` desde Perfil y `deactivate_user` / `reactivate_user` desde administración, con actor, cuenta objetivo, estado anterior/nuevo e IP. La edición administrativa conserva `update_user`, con rol, estado y nombres de campos modificados, sin copiar contraseñas, tokens ni identificadores OAuth. Las bitácoras anteriores se conservan.

## Reportes

La creación y edición aceptan únicamente los campos validados del formulario. Propietario, autor, actor de modificación, método, actor y fecha de validación se asignan en el servidor. Se conservan la unicidad VIN/placa y reporte por vehículo, las transacciones, autorización, auditoría y límites existentes (30 escrituras por hora y 60 solicitudes por minuto al recurso). Las pruebas cubren la suplantación de metadatos en creación y edición.

Registro continúa publicando automáticamente al cumplir las validaciones actuales. La validación automática comprueba integridad y autorización; no acredita independientemente el hecho ni certifica una verificación por autoridad. No se añade revisión manual ni bloqueo por sospecha. La señal de frecuencia existente queda auditada sin modificar el estado de los reportes.

## Alcance y riesgos

- No hay eliminación definitiva ni anonimización automática. Una solicitud de borrado definitivo exige definir explícitamente la política de retención y privacidad, obligaciones sobre evidencias e historial y tratamiento de atribuciones. Las cascadas históricas siguen en el esquema: no ejecutar borrados físicos como sustituto del cierre normal.
- Actualización 2026-10-23: `AccountLifecycle` revoca también el hash de sesión activa al desactivar/reactivar. Esto invalida autorización en `file`, Redis y otros drivers sin enumerar sus registros. Se conserva la eliminación adicional de sesiones `database`. El driver real y el recorrido entre dos navegadores siguen pendientes de comprobación en el hosting.
- Los bloqueos de administradores se verifican funcionalmente con SQLite en memoria. Su comportamiento concurrente de producción requiere comprobación con el motor y aislamiento de producción.
- La fase 3 no requirió migraciones. La ampliación de fotografías/sesión única añade dos migraciones aditivas; véase [Fotografías y sesión única](FOTOS_Y_SESION_UNICA.md). Las sesiones previas deben volver a autenticarse. No se modificaron datos reales.

Las fotos, sus metadatos y los pendientes de limpieza se respaldan junto con vehículos/reportes. Desactivar nunca limpia imágenes. El borrado permitido de un reporte programa limpieza durable mediante su ciclo de vida; no usar cascadas SQL como sustituto del servicio.
