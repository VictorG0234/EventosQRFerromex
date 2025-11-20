<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    const UPDATED_AT = null; // Solo created_at, no updated_at

    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el modelo relacionado
     */
    public function auditable()
    {
        if (!$this->model || !$this->model_id) {
            return null;
        }

        $modelClass = "App\\Models\\{$this->model}";
        
        if (class_exists($modelClass)) {
            return $modelClass::find($this->model_id);
        }

        return null;
    }

    /**
     * Registrar una acción en el log
     */
    public static function log(
        string $action,
        ?string $model = null,
        ?int $modelId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scopes
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModel($query, string $model)
    {
        return $query->where('model', $model);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Obtener nombre legible de la acción
     */
    public function getActionNameAttribute(): string
    {
        $actions = [
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            'scan' => 'Escaneo QR',
            'raffle' => 'Sorteo',
            'import' => 'Importación',
            'export' => 'Exportación',
            'email_sent' => 'Envío de email',
            'manual_registration' => 'Registro manual',
        ];

        return $actions[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Limpiar logs antiguos (para comando scheduled)
     */
    public static function cleanOld(int $days = 30): int
    {
        return self::where('created_at', '<', now()->subDays($days))->delete();
    }
}
