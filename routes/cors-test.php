<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CorsTestController;

// === RUTAS DE PRUEBA CORS ===
Route::options('cors-test', [CorsTestController::class, 'options']);
Route::get('cors-test', [CorsTestController::class, 'test']);
Route::post('cors-test', [CorsTestController::class, 'test']);

Route::options('cors-login', [CorsTestController::class, 'options']);
Route::post('cors-login', [CorsTestController::class, 'login']);

// === RUTA SIMPLE PARA DEBUG ===
Route::any('debug', function() {
    $response = response()->json([
        'status' => 'OK',
        'message' => 'API funcionando',
        'timestamp' => now(),
        'headers' => request()->headers->all()
    ]);
    
    return $response
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
});