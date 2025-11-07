<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    /**
     * Agregar headers CORS a cualquier respuesta
     */
    private function addCorsHeaders($response)
    {
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->header('Access-Control-Allow-Credentials', 'false');
    }

    /**
     * Manejar OPTIONS request para CORS
     */
    public function options()
    {
        return $this->addCorsHeaders(response('', 200));
    }

    /**
     * Registrar nuevo usuario
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed'
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => md5($validatedData['password'])
            ]);

            $token = $this->generateJWT($user);

            return $this->addCorsHeaders(response()->json([
                'message' => 'Usuario registrado exitosamente',
                'user' => $user,
                'token' => $token
            ], 201));

        } catch (ValidationException $e) {
            return $this->addCorsHeaders(response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422));
        }
    }

    /**
     * Iniciar sesión
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Log para debug
            Log::info('Login attempt started', ['request_data' => $request->all()]);
            
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            Log::info('Validation passed', ['credentials' => ['email' => $credentials['email']]]);

            // Buscar usuario por email
            Log::info('Searching for user with email: ' . $credentials['email']);
            $user = User::where('email', $credentials['email'])->first();

            Log::info('User search result', ['user_found' => $user ? 'yes' : 'no']);

            // Verificar si el usuario existe y la contraseña es correcta
            if ($user) {
                $passwordVerified = false;
                
                // Primero intentar con MD5 (para usuarios existentes)
                if (md5($credentials['password']) === $user->password) {
                    Log::info('Password verified with MD5');
                    $passwordVerified = true;
                } 
                // Luego intentar con Bcrypt (para usuarios nuevos)
                else if (Hash::check($credentials['password'], $user->password)) {
                    Log::info('Password verified with Bcrypt');
                    $passwordVerified = true;
                }
                
                if ($passwordVerified) {
                    $accessToken = $this->generateJWT($user);
                    $refreshToken = RefreshToken::createForUser($user->id);

                    return $this->addCorsHeaders(response()->json([
                        'message' => 'Login exitoso',
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email
                        ],
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken->token,
                        'token_type' => 'Bearer',
                        'access_token_expires_in' => 14400, // 4 horas en segundos
                        'refresh_token_expires_in' => 604800 // 7 días en segundos (7 * 24 * 60 * 60)
                    ]));
                }
            }

            Log::info('Login failed - invalid credentials');
            return $this->addCorsHeaders(response()->json([
                'message' => 'Credenciales inválidas'
            ], 401));

        } catch (ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors()]);
            return $this->addCorsHeaders(response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422));
        } catch (\Exception $e) {
            Log::error('Login error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->addCorsHeaders(response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500));
        }
    }

    /**
     * Obtener perfil del usuario autenticado
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->input('refresh_token');
            
            if ($refreshToken) {
                RefreshToken::where('token', $refreshToken)->delete();
            }
            
            return response()->json([
                'message' => 'Logout exitoso'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Logout exitoso'
            ]);
        }
    }

    /**
     * Refrescar access token usando refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string'
            ]);

            $refreshToken = RefreshToken::findValidToken($request->refresh_token);
            
            if (!$refreshToken) {
                return response()->json([
                    'message' => 'Refresh token inválido o expirado'
                ], 401);
            }

            $user = $refreshToken->user;
            $newAccessToken = $this->generateJWT($user);
            
            // Opcional: Generar nuevo refresh token (rotación)
            $newRefreshToken = RefreshToken::createForUser($user->id);

            return response()->json([
                'message' => 'Token refrescado exitosamente',
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken->token,
                'token_type' => 'Bearer',
                'access_token_expires_in' => 14400, // 4 horas
                'refresh_token_expires_in' => 604800 // 7 días
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Refresh token error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al refrescar token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar token JWT
     */
    private function generateJWT(User $user): string
    {
        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => time(),
            'exp' => time() + (60 * 60 * 4), // 4 horas
            'user_id' => $user->id,
            'email' => $user->email
        ];

        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }

    /**
     * Validar token JWT
     */
    public static function validateJWT(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}