<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CorsTestController extends Controller
{
    public function test(Request $request)
    {
        // Headers CORS directos
        $response = response()->json([
            'message' => 'CORS funcionando correctamente',
            'method' => $request->method(),
            'origin' => $request->header('Origin'),
            'timestamp' => now()
        ]);

        return $this->addCorsHeaders($response);
    }

    public function login(Request $request)
    {
        // Simular el login con CORS
        $credentials = $request->only('email', 'password');
        
        if ($credentials['email'] === 'admin@invoice.com' && $credentials['password'] === 'admin123') {
            $response = response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => [
                    'id' => 1,
                    'name' => 'Admin',
                    'email' => 'admin@invoice.com',
                    'role' => 'admin'
                ],
                'token' => 'fake-jwt-token-for-testing'
            ]);
        } else {
            $response = response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        return $this->addCorsHeaders($response);
    }

    public function options()
    {
        $response = response('', 200);
        return $this->addCorsHeaders($response);
    }

    private function addCorsHeaders($response)
    {
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->header('Access-Control-Allow-Credentials', 'false')
            ->header('Access-Control-Max-Age', '86400');
    }
}