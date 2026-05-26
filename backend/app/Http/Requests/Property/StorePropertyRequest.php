<?php

namespace App\Http\Requests\Property;

use App\Enums\FurnishingType;
use App\Enums\PropertyType;
use App\Enums\RentalPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isRoom = $this->input('property_type') === PropertyType::Room->value;

        return [
            'property_type'      => ['required', Rule::enum(PropertyType::class)],
            'title'              => ['required', 'string', 'max:255'],
            'short_description'  => ['nullable', 'string', 'max:500'],
            'description'        => ['nullable', 'string', 'max:10000'],

            'voivodeship'        => ['required', 'string', 'max:100'],
            'city'               => ['required', 'string', 'max:100'],
            'district'           => ['nullable', 'string', 'max:100'],
            'street'             => ['nullable', 'string', 'max:200'],
            'postal_code'        => ['nullable', 'string', 'max:10'],

            'area'               => $isRoom ? ['nullable', 'numeric', 'min:1'] : ['required', 'numeric', 'min:1'],
            'rooms_count'        => $isRoom ? ['nullable', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'bathrooms_count'    => ['nullable', 'integer', 'min:0'],
            'floor'              => ['nullable', 'integer', 'min:-2', 'max:100'],
            'total_floors'       => ['nullable', 'integer', 'min:1'],
            'furnishing'         => ['nullable', Rule::enum(FurnishingType::class)],
            'year_built'         => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 2)],
            'parking'            => ['boolean'],
            'elevator'           => ['boolean'],
            'balcony'            => ['boolean'],

            'room_area'          => $isRoom ? ['required', 'numeric', 'min:1'] : ['nullable'],
            'apartment_area'     => $isRoom ? ['required', 'numeric', 'min:1'] : ['nullable'],
            'roommates_count'    => $isRoom ? ['nullable', 'integer', 'min:0'] : ['nullable'],
            'total_rooms_count'  => $isRoom ? ['nullable', 'integer', 'min:1'] : ['nullable'],

            'price'              => ['required', 'numeric', 'min:1'],
            'admin_fee'          => ['nullable', 'numeric', 'min:0'],
            'deposit'            => ['nullable', 'numeric', 'min:0'],
            'additional_costs'   => ['nullable', 'string', 'max:1000'],

            'rental_period'      => ['nullable', Rule::enum(RentalPeriod::class)],
            'available_from'     => ['nullable', 'date', 'after_or_equal:today'],
            'min_rental_months'  => ['nullable', 'integer', 'min:1'],

            'pets_allowed'       => ['boolean'],
            'smoking_allowed'    => ['boolean'],
            'students_allowed'   => ['boolean'],
            'only_women'         => ['boolean'],
            'only_men'           => ['boolean'],

            'rules'              => ['nullable', 'string', 'max:2000'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'property_type.required' => 'Typ oferty jest wymagany.',
            'title.required'         => 'Tytuł jest wymagany.',
            'voivodeship.required'   => 'Województwo jest wymagane.',
            'city.required'          => 'Miasto jest wymagane.',
            'area.required'          => 'Metraż jest wymagany.',
            'rooms_count.required'   => 'Liczba pokoi jest wymagana.',
            'price.required'         => 'Cena jest wymagana.',
            'room_area.required'     => 'Metraż pokoju jest wymagany.',
            'apartment_area.required'=> 'Metraż mieszkania jest wymagany.',
        ];
    }
}
