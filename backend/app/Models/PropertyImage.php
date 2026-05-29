<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyImage extends Model
{
    protected $fillable = [
        'property_id', 'path', 'filename', 'order', 'is_main',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
