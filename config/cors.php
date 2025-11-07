<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => ['*'], // Temporal para desarrollo
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 3600, // Cache preflight por 1 hora
    
    'supports_credentials' => false, // Temporal para desarrollo
];