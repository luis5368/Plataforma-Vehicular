# Plataforma Vehicular

Aplicación Laravel 12 para registrar y consultar vehículos y reportes en Guatemala.

Consulta [instalación, migraciones, permisos, seguridad y validación](docs/IMPLEMENTACION.md).

Inicio local: `composer install`, configurar `.env`, `php artisan key:generate`, `php artisan migrate`, `php artisan db:seed --class=RoleSeeder`, `php artisan app:create-admin`, `npm install`, `npm run build` y `php artisan serve`.

Pruebas: `php artisan test --compact`. En Windows puede usarse `npm.cmd run build` cuando PowerShell restringe scripts.
