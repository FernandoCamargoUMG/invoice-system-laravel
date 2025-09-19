<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::select('id', 'name', 'email', 'role', 'created_at')->get();
            
            return response()->json([
                'message' => 'Usuarios obtenidos exitosamente',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un usuario específico
     */
    public function show(User $user): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Usuario obtenido exitosamente',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener usuario', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email|max:100',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,cashier'
            ]);

            // Usar MD5 para mantener compatibilidad
            $validatedData['password'] = md5($validatedData['password']);

            $user = User::create($validatedData);

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear usuario', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:100',
                'email' => 'sometimes|email|unique:users,email,' . $user->id . '|max:100',
                'password' => 'sometimes|string|min:6',
                'role' => 'sometimes|in:admin,cashier'
            ]);

            // Si se proporciona password, hashearlo con MD5
            if (isset($validatedData['password'])) {
                $validatedData['password'] = md5($validatedData['password']);
            }

            $user->update($validatedData);

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar usuario', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un usuario
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // Prevenir eliminación del propio usuario (opcional, requiere middleware jwt)
            // Por ahora, permitir eliminación de cualquier usuario
            $user->delete();

            return response()->json([
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al eliminar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar rol de un usuario
     */
    public function changeRole(Request $request, User $user): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'role' => 'required|in:admin,cashier'
            ]);

            $user->update(['role' => $validatedData['role']]);

            return response()->json([
                'message' => 'Rol actualizado exitosamente',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al cambiar rol', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al cambiar rol',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}