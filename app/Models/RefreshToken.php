<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class RefreshToken extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verificar si el token ha expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Generar un nuevo refresh token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Crear un nuevo refresh token para un usuario
     */
    public static function createForUser(int $userId): self
    {
    // Eliminar tokens anteriores del usuario para evitar duplicados
        self::where('user_id', $userId)->delete();

        return self::create([
            'user_id' => $userId,
            'token' => self::generateToken(),
            'expires_at' => Carbon::now()->addDays(7) // Token válido por 7 días
        ]);
    }

    /**
     * Buscar un token válido
     */
    public static function findValidToken(string $token): ?self
    {
        $refreshToken = self::where('token', $token)->first();

        if (!$refreshToken || $refreshToken->isExpired()) {
            // Eliminar token expirado si existe
            if ($refreshToken) {
                $refreshToken->delete();
            }
            return null;
        }

        return $refreshToken;
    }
}