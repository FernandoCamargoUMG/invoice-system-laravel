<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        // Desarrollo local
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        
        // Producción (usar variable de entorno)
        ...array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => [
        'Accept',
        'Content-Type', 
        'Authorization',
        'X-Requested-With',
    ],
    
    'exposed_headers' => [],
    
    'max_age' => 3600, // Cache preflight por 1 hora
    
    'supports_credentials' => true,
];