<?php

namespace App\Http\Requests\Api;

use App\Enums\BodyType;
use App\Enums\City;
use App\Enums\Color;
use App\Enums\Condition;
use App\Enums\FuelType;
use App\Enums\Origin;
use App\Enums\Transmission;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CarIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $nextYear = (int) date('Y') + 1;
        $maxPerPage = (int) config('cars.max_per_page', 60);

        return [
            'search' => ['nullable', 'string', 'max:100'],

            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'body_type' => ['nullable', 'string', 'in:'.$this->enumValues(BodyType::cases())],
            'color' => ['nullable', 'string', 'in:'.$this->enumValues(Color::cases())],
            'origin' => ['nullable', 'string', 'in:'.$this->enumValues(Origin::cases())],
            'city' => ['nullable', 'string', 'in:'.$this->enumValues(City::cases())],

            'year_min' => ['nullable', 'integer', 'min:1900', 'max:'.$nextYear],
            'year_max' => ['nullable', 'integer', 'min:1900', 'max:'.$nextYear],

            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],

            'mileage_min' => ['nullable', 'integer', 'min:0'],
            'mileage_max' => ['nullable', 'integer', 'min:0'],

            'transmission' => ['nullable', 'string', 'in:'.$this->enumValues(Transmission::cases())],
            'fuel_type' => ['nullable', 'string', 'in:'.$this->enumValues(FuelType::cases())],
            'condition' => ['nullable', 'string', 'in:'.$this->enumValues(Condition::cases())],

            'is_featured' => ['nullable', 'boolean'],

            'sort' => [
                'nullable',
                'string',
                'in:newest,oldest,price_low,price_high,mileage_low,mileage_high,year_new,year_old',
            ],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$maxPerPage],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($this->filled('year_min') && $this->filled('year_max')
                && (int) $this->input('year_min') > (int) $this->input('year_max')) {
                $v->errors()->add('year_min', 'year_min cannot be greater than year_max.');
            }
            if ($this->filled('price_min') && $this->filled('price_max')
                && (float) $this->input('price_min') > (float) $this->input('price_max')) {
                $v->errors()->add('price_min', 'price_min cannot be greater than price_max.');
            }
            if ($this->filled('mileage_min') && $this->filled('mileage_max')
                && (int) $this->input('mileage_min') > (int) $this->input('mileage_max')) {
                $v->errors()->add('mileage_min', 'mileage_min cannot be greater than mileage_max.');
            }
        });
    }

    /**
     * Validated filter payload to feed to CarFilterService.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $data = $this->safe()->only([
            'search', 'brand', 'model', 'body_type', 'color',
            'year_min', 'year_max', 'price_min', 'price_max',
            'origin', 'mileage_min', 'mileage_max',
            'transmission', 'fuel_type', 'condition', 'city',
            'sort',
        ]);

        if ($this->has('is_featured')) {
            $data['is_featured'] = $this->boolean('is_featured');
        }

        return $data;
    }

    public function perPage(): int
    {
        $default = (int) config('cars.default_per_page', 12);
        $max = (int) config('cars.max_per_page', 60);

        $value = (int) ($this->validated('per_page') ?? $default);

        return max(1, min($value, $max));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * @param  array<int, \UnitEnum>  $cases
     */
    protected function enumValues(array $cases): string
    {
        return collect($cases)->pluck('value')->implode(',');
    }
}
