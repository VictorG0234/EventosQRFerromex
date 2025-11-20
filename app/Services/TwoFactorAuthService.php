<?php

namespace App\Services;

use App\Models\TwoFactorCode;
use App\Models\User;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TwoFactorAuthService
{
    /**
     * Generar y enviar código 2FA
     */
    public function generateAndSend(User $user, string $ipAddress): TwoFactorCode
    {
        // Invalidar códigos anteriores no usados
        TwoFactorCode::forUser($user->id)
            ->valid()
            ->delete();

        // Generar nuevo código
        $code = TwoFactorCode::generateCode();
        
        // Crear registro en base de datos
        $twoFactorCode = TwoFactorCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10), // Expira en 10 minutos
            'ip_address' => $ipAddress,
        ]);

        // Enviar email con el código
        // NOTA: Comentado hasta tener SMTP configurado
        // Mail::to($user->email)->send(new TwoFactorCodeMail($twoFactorCode));

        return $twoFactorCode;
    }

    /**
     * Verificar código 2FA
     */
    public function verify(User $user, string $code): bool
    {
        $twoFactorCode = TwoFactorCode::forUser($user->id)
            ->where('code', strtoupper($code))
            ->valid()
            ->first();

        if (!$twoFactorCode) {
            return false;
        }

        // Marcar como usado
        $twoFactorCode->markAsUsed();

        return true;
    }

    /**
     * Verificar si el usuario requiere 2FA
     */
    public function requiresTwoFactor(User $user): bool
    {
        // Por ahora, todos los usuarios administradores requieren 2FA
        // Se puede expandir con roles o configuraciones
        return true;
    }

    /**
     * Obtener código activo para usuario (solo para debugging/testing)
     */
    public function getActiveCode(User $user): ?TwoFactorCode
    {
        return TwoFactorCode::forUser($user->id)
            ->valid()
            ->latest()
            ->first();
    }

    /**
     * Limpiar códigos expirados
     */
    public function cleanExpiredCodes(): int
    {
        return TwoFactorCode::cleanExpired();
    }
}
