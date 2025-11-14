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
        'compania',
        'numero_empleado',
        'nombre_completo',
        'correo',
        'puesto',
        'nivel_de_puesto',
        'localidad',
        'fecha_alta',
        'descripcion',
        'categoria_rifa',
        'qr_code',
        'qr_code_path',
        'email_sent',
    ];

    protected $casts = [
        'fecha_alta' => 'date',
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
        return $this->nombre_completo;
    }

    public function hasAttended(): bool
    {
        return $this->attendance !== null;
    }

    public function canParticipateInPrize(Prize $prize): bool
    {
        return $this->categoria_rifa === $prize->category && $this->hasAttended();
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
            'compania' => $this->compania,
            'numero_empleado' => $this->numero_empleado,
            'nombre_completo' => $this->nombre_completo,
            'correo' => $this->correo,
            'puesto' => $this->puesto,
            'localidad' => $this->localidad,
            'qr_code' => $this->qr_code,
        ];
    }
}
