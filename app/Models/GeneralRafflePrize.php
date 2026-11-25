<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralRafflePrize extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'category',
        'value',
        'active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
