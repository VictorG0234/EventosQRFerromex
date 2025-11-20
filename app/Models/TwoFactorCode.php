<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TwoFactorCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generar código aleatorio de 6 caracteres
     */
    public static function generateCode(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Sin caracteres confusos: I,O,0,1
        $code = '';
        
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $code;
    }

    /**
     * Verificar si el código está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Verificar si el código ya fue usado
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * Verificar si el código es válido
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }

    /**
     * Marcar código como usado
     */
    public function markAsUsed(): void
    {
        $this->update([
            'used_at' => now(),
        ]);
    }

    /**
     * Scope para códigos válidos (no expirados y no usados)
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                    ->whereNull('used_at');
    }

    /**
     * Scope para códigos de un usuario específico
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Limpiar códigos expirados (para comando scheduled)
     */
    public static function cleanExpired(): int
    {
        return self::where('expires_at', '<', now()->subDays(7))->delete();
    }
}
