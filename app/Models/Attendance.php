<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_id',
        'scanned_at',
        'scanned_by',
        'scan_metadata',
        'scan_count',
        'last_scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'last_scanned_at' => 'datetime',
        'scan_metadata' => 'array',
    ];

    // Constante para el límite de escaneos
    public const MAX_SCAN_COUNT = 2;

    // Relaciones
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    // Métodos útiles
    public static function registerAttendance(Guest $guest, ?string $scannedBy = null, array $metadata = []): self
    {
        return self::create([
            'event_id' => $guest->event_id,
            'guest_id' => $guest->id,
            'scanned_at' => Carbon::now(),
            'scanned_by' => $scannedBy,
            'scan_metadata' => $metadata,
        ]);
    }

    public function getTimeElapsedAttribute(): string
    {
        return $this->scanned_at->diffForHumans();
    }

    public function isRecentScan(): bool
    {
        return $this->scanned_at->diffInMinutes(Carbon::now()) <= 5;
    }

    /**
     * Verificar si ha excedido el límite de escaneos
     */
    public function hasExceededLimit(): bool
    {
        return $this->scan_count > self::MAX_SCAN_COUNT;
    }

    /**
     * Incrementar el contador de escaneos
     */
    public function incrementScanCount(): void
    {
        $this->increment('scan_count');
        $this->update(['last_scanned_at' => Carbon::now()]);
    }
}
