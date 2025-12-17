<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class RaffleEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_id',
        'prize_id',
        'status',
        'position',
        'participated_at',
        'drawn_at',
        'prize_delivered',
        'delivered_at',
        'delivered_by',
        'raffle_metadata',
    ];

    protected $casts = [
        'participated_at' => 'datetime',
        'drawn_at' => 'datetime',
        'delivered_at' => 'datetime',
        'prize_delivered' => 'boolean',
        'raffle_metadata' => 'array',
    ];

    // Relaciones
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    // Métodos útiles
    public static function enterRaffle(Guest $guest, Prize $prize, array $metadata = []): self
    {
        return self::create([
            'event_id' => $guest->event_id,
            'guest_id' => $guest->id,
            'prize_id' => $prize->id,
            'status' => 'pending',
            'participated_at' => Carbon::now(),
            'raffle_metadata' => $metadata,
        ]);
    }

    public function markAsWinner(array $metadata = []): bool
    {
        $this->update([
            'status' => 'won',
            'drawn_at' => Carbon::now(),
            'raffle_metadata' => array_merge($this->raffle_metadata ?? [], $metadata),
        ]);

        // Solo decrementar stock si hay un premio asociado y no es el premio especial "Rifa General"
        if ($this->prize && $this->prize->name !== 'Rifa General') {
            return $this->prize->decrementStock();
        }
        
        return true; // Para rifa general o premios especiales
    }

    public function markAsLoser(array $metadata = []): void
    {
        $this->update([
            'status' => 'lost',
            'drawn_at' => Carbon::now(),
            'raffle_metadata' => array_merge($this->raffle_metadata ?? [], $metadata),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isWinner(): bool
    {
        return $this->status === 'won';
    }

    public function isLoser(): bool
    {
        return $this->status === 'lost';
    }

    public function markAsDelivered(?int $userId = null): bool
    {
        return $this->update([
            'prize_delivered' => true,
            'delivered_at' => Carbon::now(),
            'delivered_by' => $userId ?? auth()->id(),
        ]);
    }

    public function isDelivered(): bool
    {
        return $this->prize_delivered === true;
    }
}
