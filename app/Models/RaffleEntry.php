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
        'participated_at',
        'drawn_at',
        'raffle_metadata',
    ];

    protected $casts = [
        'participated_at' => 'datetime',
        'drawn_at' => 'datetime',
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

        return $this->prize->decrementStock();
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
}
