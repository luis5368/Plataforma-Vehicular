<?php

use App\Http\Middleware\ActiveAccount;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SingleActiveSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aquí registramos el alias 'role' para usarlo en las rutas
        $middleware->alias([
            'session.current' => SingleActiveSession::class,
            'active' => ActiveAccount::class,
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            $message = 'La carga supera el límite permitido por el servidor. Reduce la cantidad o el tamaño de las fotografías e intenta nuevamente.';

            return $request->expectsJson() ? response()->json(['message' => $message], 413)
                : response()->view('errors.upload-too-large', ['message' => $message, 'errors' => new ViewErrorBag], 413);
        });
    })->create();
