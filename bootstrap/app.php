<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS personalizado como primera opción
        $middleware->use([
            \App\Http\Middleware\CustomCorsMiddleware::class,
        ]);
        
        // Para rutas API específicamente
        $middleware->group('api', [
            \App\Http\Middleware\CustomCorsMiddleware::class,
        ]);

        // Alias de middlewares personalizados
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtMiddleware::class,
            'cors' => \App\Http\Middleware\CustomCorsMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
