<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Si Laravel está en modo mantenimiento
if (file_exists(__DIR__.'/storage/framework/maintenance.php')) {
    require __DIR__.'/storage/framework/maintenance.php';
}

// Cargar Composer autoload
require __DIR__.'/vendor/autoload.php';

// Bootstrap de Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

// Crear el kernel HTTP
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Capturar y manejar la solicitud
$response = $kernel->handle(
    $request = Request::capture()
);

// Enviar la respuesta
$response->send();

// Finalizar la ejecución
$kernel->terminate($request, $response);
