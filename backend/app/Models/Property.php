<?php

namespace App\Models;

use App\Enums\FurnishingType;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'property_type', 'status', 'title', 'slug',
        'short_description', 'description',
        'voivodeship', 'city', 'district', 'street', 'postal_code',
        'latitude', 'longitude',
        'area', 'rooms_count', 'bathrooms_count', 'floor', 'total_floors',
        'furnishing', 'year_built', 'parking', 'elevator', 'balcony',
        'room_area', 'apartment_area', 'roommates_count', 'total_rooms_count',
        'price', 'admin_fee', 'deposit', 'additional_costs',
        'rental_period', 'available_from', 'min_rental_months',
        'pets_allowed', 'smoking_allowed', 'students_allowed',
        'only_women', 'only_men',
        'rules', 'notes', 'main_image_id',
        'meta_title', 'meta_description',
        'published_at', 'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'property_type' => PropertyType::class,
            'status' => PropertyStatus::class,
            'furnishing' => FurnishingType::class,
            'rental_period' => RentalPeriod::class,
            'available_from' => 'date',
            'published_at' => 'datetime',
            'parking' => 'boolean',
            'elevator' => 'boolean',
            'balcony' => 'boolean',
            'pets_allowed' => 'boolean',
            'smoking_allowed' => 'boolean',
            'students_allowed' => 'boolean',
            'only_women' => 'boolean',
            'only_men' => 'boolean',
            'price' => 'decimal:2',
            'admin_fee' => 'decimal:2',
            'deposit' => 'decimal:2',
            'area' => 'decimal:2',
            'room_area' => 'decimal:2',
            'apartment_area' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('order');
    }

    public function mainImage(): BelongsTo
    {
        return $this->belongsTo(PropertyImage::class, 'main_image_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(PropertyFeature::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function viewings(): HasMany
    {
        return $this->hasMany(PropertyViewing::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', PropertyStatus::Published);
    }
}
