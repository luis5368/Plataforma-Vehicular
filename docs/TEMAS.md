# Diseño oscuro original

Por solicitud del usuario se retiró el sistema claro/oscuro y su selector.
Los layouts vuelven a declarar data-bs-theme="dark" y usan platform.css original.
No se consulta localStorage ni la preferencia del sistema. La preferencia guardada anteriormente queda sin efecto.
Se conservan el semáforo público, su leyenda, mapas y demás cambios funcionales.
ThemeLayoutTest comprueba el oscuro fijo en páginas públicas y módulos de cada rol.
No se modifican credenciales, dependencias, migraciones ni datos reales.
