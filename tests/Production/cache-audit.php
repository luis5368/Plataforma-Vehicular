<?php

use Illuminate\Contracts\Console\Kernel;

// Execute real Laravel cache commands with an empty environment directory and isolated caches.
require dirname(__DIR__, 2).'/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$directory = $root.'/storage/framework/production-audit/cache-check';
@mkdir($directory.'/bootstrap/cache', 0770, true);
@mkdir($directory.'/views', 0770, true);
// Clear configuration-related inherited environment variables without reading their values.
foreach (glob($root.'/config/*.php') as $file) {
    preg_match_all('/env\([\'"]([A-Z0-9_]+)[\'"]/', file_get_contents($file), $matches);
    foreach ($matches[1] as $name) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    }
}
foreach (['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_URL' => 'https://audit.invalid',
    'APP_CONFIG_CACHE' => 'storage/framework/production-audit/cache-check/config.php',
    'APP_ROUTES_CACHE' => 'storage/framework/production-audit/cache-check/routes.php',
    'VIEW_COMPILED_PATH' => $directory.'/views', 'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array',
    'LOG_CHANNEL' => 'null', 'SESSION_SECURE_COOKIE' => 'true'] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
$wrapper = '<?php $app = require '.var_export($root.'/bootstrap/app.php', true).';'
    .'$app->useEnvironmentPath('.var_export($directory, true).')->loadEnvironmentFrom(".env.absent");'
    .'$app->useBootstrapPath('.var_export($directory.'/bootstrap', true).'); return $app;';
file_put_contents($directory.'/bootstrap/app.php', $wrapper);
file_put_contents($directory.'/bootstrap/providers.php', '<?php return require '.var_export($root.'/bootstrap/providers.php', true).';');
$app = require $directory.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
try {
    foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
        if ($kernel->call($command) !== 0) {
            throw new RuntimeException($command.' failed');
        }
        echo 'PASS '.$command.PHP_EOL;
    }
    $cached = require $directory.'/config.php';
    if ($cached['app']['env'] !== 'production' || $cached['app']['debug'] || ! $cached['session']['secure'] || $cached['app']['key'] !== null) {
        throw new RuntimeException('Isolated production configuration check failed');
    }
    echo 'PASS synthetic production configuration; no application key cached'.PHP_EOL;
    $fresh = require $directory.'/bootstrap/app.php';
    $fresh->make(Kernel::class)->bootstrap();
    if (! $fresh->configurationIsCached() || ! $fresh->routesAreCached() || ! $fresh['router']->getRoutes()->getByName('consultar.region.map')) {
        throw new RuntimeException('Cached application failed to boot');
    }
    echo 'PASS fresh application boots with cached routes and config'.PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL '.get_class($exception).' at '.basename($exception->getFile()).':'.$exception->getLine().PHP_EOL);
    exit(1);
} finally {
    foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
        $kernel->call($command);
        echo 'PASS '.$command.PHP_EOL;
    }
    if (is_file($directory.'/config.php') || is_file($directory.'/routes.php') || glob($directory.'/views/*.php')) {
        throw new RuntimeException('Cache cleanup failed');
    }
}
