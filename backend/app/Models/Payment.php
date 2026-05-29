<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'property_id', 'owner_id', 'tenant_id', 'contract_id',
        'type', 'status', 'amount', 'description',
        'due_date', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'status' => PaymentStatus::class,
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
