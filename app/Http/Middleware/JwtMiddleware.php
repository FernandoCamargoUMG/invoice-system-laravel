<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token de autorización requerido'
            ], 401);
        }

        $decoded = AuthController::validateJWT($token);

        if (!$decoded) {
            return response()->json([
                'message' => 'Token inválido o expirado'
            ], 401);
        }

        // Buscar el usuario
        $user = User::find($decoded['user_id']);

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 401);
        }

        // Agregar el usuario al request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}