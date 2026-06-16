<?php

namespace App\Services;

use App\Enums\BodyType;
use App\Enums\City;
use App\Enums\Color;
use App\Enums\Condition;
use App\Enums\Drivetrain;
use App\Enums\FuelType;
use App\Enums\Origin;
use App\Enums\Transmission;
use App\Models\Car;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class CarFilterOptionsService
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        // Enum labels are localised, so cache per-locale.
        $base = (string) config('cars.filter_options_cache_key', 'cars:filter-options');
        $key = $base.':'.app()->getLocale();

        return Cache::remember(
            $key,
            (int) config('cars.filter_options_cache_ttl', 600),
            fn () => $this->build(),
        );
    }

    public function flush(): void
    {
        $base = (string) config('cars.filter_options_cache_key', 'cars:filter-options');
        foreach ((array) config('app.available_locales', ['ar', 'en']) as $loc) {
            Cache::forget($base.':'.$loc);
        }
        Cache::forget($base); // legacy / non-localized key
    }

    /**
     * @return array<string, mixed>
     */
    protected function build(): array
    {
        // Single query for all 6 MIN/MAX aggregates instead of 3 round trips.
        $ranges = $this->numericRanges(['year', 'price', 'mileage']);

        // Only return enum options actually present on visible cars so the
        // frontend doesn't show empty filter values.
        $usedColors = $this->distinctValues('color');
        $usedOrigins = $this->distinctValues('origin');
        $usedCities = $this->distinctValues('city');

        return [
            'locale' => app()->getLocale(),
            'brands' => $this->distinctValues('brand'),
            'models_by_brand' => $this->modelsByBrand(),
            'body_types' => $this->enumOptions(BodyType::cases()),
            'transmissions' => $this->enumOptions(Transmission::cases()),
            'fuel_types' => $this->enumOptions(FuelType::cases()),
            'conditions' => $this->enumOptions(Condition::cases()),
            'drivetrains' => $this->enumOptions(Drivetrain::cases()),
            'colors' => $this->enumOptions(Color::cases(), $usedColors),
            'origins' => $this->enumOptions(Origin::cases(), $usedOrigins),
            'cities' => $this->enumOptions(City::cases(), $usedCities),
            'year_range' => $ranges['year'],
            'price_range' => $ranges['price'],
            'mileage_range' => $ranges['mileage'],
            'sort_options' => $this->sortOptions(),
        ];
    }

    protected function baseQuery(): Builder
    {
        return Car::query()->visible();
    }

    /**
     * @return array<int, string>
     */
    protected function distinctValues(string $column): array
    {
        // toBase() bypasses Eloquent's attribute casts so an enum-cast column
        // (color, origin, city) returns its raw string value, not a backed
        // enum case. Without this, downstream in_array() checks fail silently.
        return $this->baseQuery()
            ->toBase()
            ->select($column)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($v) => is_object($v) && property_exists($v, 'value') ? $v->value : (string) $v)
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function modelsByBrand(): array
    {
        return $this->baseQuery()
            ->select('brand', 'model')
            ->whereNotNull('brand')
            ->whereNotNull('model')
            ->where('brand', '!=', '')
            ->where('model', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->groupBy('brand')
            ->map(fn ($group) => $group->pluck('model')->values()->all())
            ->all();
    }

    /**
     * Compute MIN/MAX for several columns in a single round trip.
     *
     * @param  array<int, string>  $columns  Whitelisted internally — values must be valid column names.
     * @return array<string, array{min: int|float|null, max: int|float|null}>
     */
    protected function numericRanges(array $columns): array
    {
        $allowed = ['year', 'price', 'mileage'];
        $columns = array_values(array_intersect($columns, $allowed));

        $select = [];
        foreach ($columns as $col) {
            $select[] = "MIN({$col}) as min_{$col}";
            $select[] = "MAX({$col}) as max_{$col}";
        }

        $row = $this->baseQuery()->selectRaw(implode(', ', $select))->first();

        $result = [];
        foreach ($columns as $col) {
            $minKey = "min_{$col}";
            $maxKey = "max_{$col}";
            $result[$col] = [
                'min' => $row?->{$minKey} !== null ? 0 + $row->{$minKey} : null,
                'max' => $row?->{$maxKey} !== null ? 0 + $row->{$maxKey} : null,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, \UnitEnum>  $cases
     * @param  array<int, string>|null  $onlyValues  If provided, only return cases whose value is in the list.
     * @return array<int, array{value: string, label: string}>
     */
    protected function enumOptions(array $cases, ?array $onlyValues = null): array
    {
        return collect($cases)
            ->when($onlyValues !== null, fn ($c) => $c->filter(
                fn ($case) => in_array($case->value, $onlyValues, true)
            ))
            ->map(fn ($case) => [
                'value' => $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : $case->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function sortOptions(): array
    {
        return [
            ['value' => 'newest', 'label' => (string) __('cars.sort.newest')],
            ['value' => 'oldest', 'label' => (string) __('cars.sort.oldest')],
            ['value' => 'price_low', 'label' => (string) __('cars.sort.price_low')],
            ['value' => 'price_high', 'label' => (string) __('cars.sort.price_high')],
            ['value' => 'mileage_low', 'label' => (string) __('cars.sort.mileage_low')],
            ['value' => 'mileage_high', 'label' => (string) __('cars.sort.mileage_high')],
            ['value' => 'year_new', 'label' => (string) __('cars.sort.year_new')],
            ['value' => 'year_old', 'label' => (string) __('cars.sort.year_old')],
        ];
    }
}
