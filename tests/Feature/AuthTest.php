<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
    // Preparar datos de prueba
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => md5('admin123'),
            'role' => 'admin'
        ]);

    // Ejecutar acción
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'admin123'
        ]);

    // Verificar resultado
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'user',
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'access_token_expires_in',
                    'refresh_token_expires_in'
                ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'admin123'
        ]);

        $response->assertStatus(401)
                ->assertJson(['message' => 'Credenciales inválidas']);
    }

    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create([
            'password' => md5('admin123')
        ]);

    // Realizar login primero
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'admin123'
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

    // Probar endpoint de refresh
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'access_token',
                    'refresh_token'
                ]);
    }
}