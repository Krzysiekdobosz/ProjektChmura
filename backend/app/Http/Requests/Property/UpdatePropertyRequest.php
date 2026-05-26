<?php

namespace App\Http\Requests\Property;

use App\Enums\FurnishingType;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\RentalPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isRoom = $this->input('property_type') === PropertyType::Room->value;

        return [
            'property_type'      => ['sometimes', Rule::enum(PropertyType::class)],
            'title'              => ['sometimes', 'string', 'max:255'],
            'short_description'  => ['nullable', 'string', 'max:500'],
            'description'        => ['nullable', 'string', 'max:10000'],

            'voivodeship'        => ['sometimes', 'string', 'max:100'],
            'city'               => ['sometimes', 'string', 'max:100'],
            'district'           => ['nullable', 'string', 'max:100'],
            'street'             => ['nullable', 'string', 'max:200'],
            'postal_code'        => ['nullable', 'string', 'max:10'],

            'area'               => ['nullable', 'numeric', 'min:1'],
            'rooms_count'        => ['nullable', 'integer', 'min:1'],
            'bathrooms_count'    => ['nullable', 'integer', 'min:0'],
            'floor'              => ['nullable', 'integer', 'min:-2', 'max:100'],
            'total_floors'       => ['nullable', 'integer', 'min:1'],
            'furnishing'         => ['nullable', Rule::enum(FurnishingType::class)],
            'year_built'         => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 2)],
            'parking'            => ['boolean'],
            'elevator'           => ['boolean'],
            'balcony'            => ['boolean'],

            'room_area'          => ['nullable', 'numeric', 'min:1'],
            'apartment_area'     => ['nullable', 'numeric', 'min:1'],
            'roommates_count'    => ['nullable', 'integer', 'min:0'],
            'total_rooms_count'  => ['nullable', 'integer', 'min:1'],

            'price'              => ['sometimes', 'numeric', 'min:1'],
            'admin_fee'          => ['nullable', 'numeric', 'min:0'],
            'deposit'            => ['nullable', 'numeric', 'min:0'],
            'additional_costs'   => ['nullable', 'string', 'max:1000'],

            'rental_period'      => ['nullable', Rule::enum(RentalPeriod::class)],
            'available_from'     => ['nullable', 'date'],
            'min_rental_months'  => ['nullable', 'integer', 'min:1'],

            'pets_allowed'       => ['boolean'],
            'smoking_allowed'    => ['boolean'],
            'students_allowed'   => ['boolean'],
            'only_women'         => ['boolean'],
            'only_men'           => ['boolean'],

            'rules'              => ['nullable', 'string', 'max:2000'],
            'notes'              => ['nullable', 'string', 'max:2000'],

            'status'             => ['sometimes', Rule::in([
                PropertyStatus::Draft->value,
                PropertyStatus::Pending->value,
                PropertyStatus::Archived->value,
            ])],
        ];
    }
}
