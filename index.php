<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// === CORS BRUTAL - ANTES DE TODO ===
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: false');

// Manejar OPTIONS requests directamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit(0);
}

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
