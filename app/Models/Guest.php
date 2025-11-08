<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'nombre',
        'apellido_p',
        'apellido_m',
        'numero_empleado',
        'area_laboral',
        'premios_rifa',
        'qr_code',
        'qr_code_path',
        'qr_code_data',
        'email',
        'email_sent',
    ];

    protected $casts = [
        'premios_rifa' => 'array',
        'email_sent' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($guest) {
            if (empty($guest->qr_code)) {
                $guest->qr_code = self::generateUniqueQrCode();
            }
        });
    }

    // Relaciones
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class);
    }

    public function raffleEntries(): HasMany
    {
        return $this->hasMany(RaffleEntry::class);
    }

    // Métodos útiles
    public function getFullNameAttribute(): string
    {
        return "{$this->nombre} {$this->apellido_p} {$this->apellido_m}";
    }

    public function hasAttended(): bool
    {
        return $this->attendance !== null;
    }

    public function canParticipateInPrize(Prize $prize): bool
    {
        return in_array($prize->category, $this->premios_rifa) && $this->hasAttended();
    }

    public static function generateUniqueQrCode(): string
    {
        do {
            $qrCode = 'QR-' . strtoupper(Str::random(10));
        } while (self::where('qr_code', $qrCode)->exists());
        
        return $qrCode;
    }

    public function getQrData(): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'nombre' => $this->nombre,
            'apellido_p' => $this->apellido_p,
            'apellido_m' => $this->apellido_m,
            'numero_empleado' => $this->numero_empleado,
            'area_laboral' => $this->area_laboral,
            'qr_code' => $this->qr_code,
        ];
    }
}
