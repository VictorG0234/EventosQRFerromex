<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'status',
        'settings',
        'public_token',
    ];

    protected $casts = [
        'event_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'settings' => 'array',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function raffleEntries(): HasMany
    {
        return $this->hasMany(RaffleEntry::class);
    }

    // Métodos útiles
    public function getAttendanceStats()
    {
        $totalGuests = $this->guests()->count();
        $attendedGuests = $this->attendances()->count();
        
        return [
            'total' => $totalGuests,
            'attended' => $attendedGuests,
            'percentage' => $totalGuests > 0 ? round(($attendedGuests / $totalGuests) * 100, 2) : 0,
        ];
    }

    public function getAttendanceRate(): float
    {
        $totalGuests = $this->guests()->count();
        $attendedGuests = $this->attendances()->count();
        
        return $totalGuests > 0 ? round(($attendedGuests / $totalGuests) * 100, 2) : 0;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Generate public registration URL
     */
    public function getPublicUrlAttribute(): string
    {
        return route('public.event.register', $this->public_token);
    }

    /**
     * Boot method to generate token on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->public_token)) {
                $event->public_token = bin2hex(random_bytes(16));
            }
        });
    }
}
