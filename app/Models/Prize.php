<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Prize extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'category',
        'stock',
        'value',
        'image',
        'active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'active' => 'boolean',
    ];

    // Relaciones
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function raffleEntries(): HasMany
    {
        return $this->hasMany(RaffleEntry::class);
    }

    // Métodos útiles
    public function isAvailable(): bool
    {
        return $this->active && $this->stock > 0;
    }

    public function getWinnersCount(): int
    {
        return $this->raffleEntries()->where('status', 'won')->count();
    }

    public function getParticipantsCount(): int
    {
        return $this->raffleEntries()->count();
    }

    public function decrementStock(int $amount = 1): bool
    {
        if ($this->stock >= $amount) {
            $this->decrement('stock', $amount);
            return true;
        }
        return false;
    }

    public function getStockPercentage(): float
    {
        // Para premios individuales (stock siempre es 1 o 0)
        // Si stock > 0, está disponible (100%), si no, no está disponible (0%)
        return $this->stock > 0 ? 100.0 : 0.0;
    }

    public function getEligibleGuests()
    {
        // Todos los premios se pueden sortear con todos los usuarios que hayan asistido
        return Guest::where('event_id', $this->event_id)
            ->whereHas('attendance')
            ->get();
    }

    /**
     * Restaurar el stock basándose en los ganadores reales registrados
     * Si no hay ganadores pero el stock es 0, restaura el stock a 1
     * @return bool True si se restauró el stock, False si no era necesario
     */
    public function restoreStockFromWinners(): bool
    {
        // No restaurar stock para el premio especial "Rifa General"
        if ($this->name === 'Rifa General') {
            return false;
        }

        // Contar ganadores reales registrados
        $winnersCount = $this->raffleEntries()->where('status', 'won')->count();

        // Si no hay ganadores pero el stock es 0, restaurar a 1
        if ($winnersCount === 0 && $this->stock === 0) {
            $this->update(['stock' => 1]);
            return true;
        }

        // Si hay ganadores pero el stock no es 0, ajustar el stock
        // (esto puede pasar si hay corrupción de datos)
        if ($winnersCount > 0 && $this->stock > 0) {
            // El stock debería ser 0 si hay ganadores (para premios con stock inicial de 1)
            // Pero no lo cambiamos automáticamente para evitar problemas
            // Solo registramos en el log
            Log::warning('Inconsistencia detectada: Premio tiene ganadores pero stock > 0', [
                'prize_id' => $this->id,
                'prize_name' => $this->name,
                'winners_count' => $winnersCount,
                'current_stock' => $this->stock
            ]);
        }

        return false;
    }
}
