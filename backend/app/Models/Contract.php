<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'property_id', 'owner_id', 'tenant_id', 'status',
        'owner_name', 'owner_address', 'owner_id_number',
        'tenant_name', 'tenant_address', 'tenant_id_number',
        'property_address', 'property_type',
        'rental_price', 'deposit_amount', 'admin_fee',
        'start_date', 'end_date', 'rental_months', 'conditions',
        'pdf_path', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'generated_at' => 'datetime',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
