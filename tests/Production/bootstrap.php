<?php

use Illuminate\Contracts\Console\Kernel;

// Dedicated throwaway MySQL only. Does not load the workspace .env.
require dirname(__DIR__, 2).'/vendor/autoload.php';

$auditRoot = dirname(__DIR__, 2).'/storage/framework/production-audit';
$auditDatabase = getenv('PV_SESSION_AUDIT_DATABASE') ?: 'pv_audit';
if ($auditDatabase !== 'pv_audit' && ! preg_match('/^pv_session_audit_[a-f0-9]{12}$/D', $auditDatabase)) {
    throw new RuntimeException('Invalid isolated audit database.');
}
if (PHP_SAPI !== 'cli' || ! is_file($auditRoot.'/mysql.pid')) {
    throw new RuntimeException('Start the isolated audit MySQL instance first.');
}

foreach ([
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_URL' => 'https://audit.invalid',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'APP_CONFIG_CACHE' => 'storage/framework/production-audit/config.php',
    'APP_ROUTES_CACHE' => 'storage/framework/production-audit/routes.php',
    'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '33079',
    'DB_DATABASE' => $auditDatabase, 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '', 'DB_URL' => '',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'database', 'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync', 'LOG_CHANNEL' => 'null', 'BCRYPT_ROUNDS' => '4',
] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->useEnvironmentPath($auditRoot)->loadEnvironmentFrom('.env.absent');
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'mysql' || config('database.connections.mysql.port') !== '33079'
    || config('database.connections.mysql.database') !== $auditDatabase) {
    throw new RuntimeException('Unsafe audit database configuration.');
}

return $app;
