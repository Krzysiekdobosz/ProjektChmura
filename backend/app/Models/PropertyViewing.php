<?php

namespace App\Models;

use App\Enums\ViewingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyViewing extends Model
{
    protected $fillable = [
        'property_id', 'user_id', 'owner_id',
        'proposed_at', 'status', 'note', 'owner_note',
    ];

    protected function casts(): array
    {
        return [
            'proposed_at' => 'datetime',
            'status' => ViewingStatus::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
