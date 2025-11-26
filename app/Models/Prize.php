<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
