<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class CarFilterService
{
    /**
     * Filter keys whose value should match exactly. Values are SAFE because
     * they're whitelisted by CarIndexRequest before they reach us.
     *
     * Every column listed here is an indexed lookup. color/origin/city store
     * enum keys (e.g. "black", "gcc", "riyadh"), so a single equality match
     * is correct for both Arabic and English clients.
     */
    protected const EQUALITY_FIELDS = [
        'brand',
        'model',
        'body_type',
        'transmission',
        'fuel_type',
        'condition',
        'color',
        'origin',
        'city',
    ];

    protected const SEARCHABLE_FIELDS = [
        'title',
        'brand',
        'model',
        'trim',
        'description',
    ];

    /**
     * Apply the validated filters + sort from the request to the given query.
     *
     * @param  Builder  $query  Already scoped to public/visible cars by the caller.
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): Builder
    {
        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyEqualityFilters($query, $filters);
        $this->applyRangeFilters($query, $filters);

        if (array_key_exists('is_featured', $filters) && $filters['is_featured'] !== null) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query;
    }

    protected function applySearch(Builder $query, ?string $term): void
    {
        $term = $term !== null ? trim($term) : '';
        if ($term === '') {
            return;
        }

        // Escape LIKE meta-characters so a user typing "%" or "_" gets a
        // literal match instead of a match-all wildcard.
        $escaped = addcslashes($term, '%_\\');
        $like = '%'.$escaped.'%';

        $query->where(function (Builder $q) use ($like): void {
            foreach (self::SEARCHABLE_FIELDS as $field) {
                $q->orWhere($field, 'like', $like);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyEqualityFilters(Builder $query, array $filters): void
    {
        foreach (self::EQUALITY_FIELDS as $field) {
            $value = $filters[$field] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $query->where($field, $value);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyRangeFilters(Builder $query, array $filters): void
    {
        $ranges = [
            'year' => ['year_min', 'year_max'],
            'price' => ['price_min', 'price_max'],
            'mileage' => ['mileage_min', 'mileage_max'],
        ];

        foreach ($ranges as $column => [$minKey, $maxKey]) {
            $min = $filters[$minKey] ?? null;
            $max = $filters[$maxKey] ?? null;

            if ($min !== null && $min !== '') {
                $query->where($column, '>=', $min);
            }
            if ($max !== null && $max !== '') {
                $query->where($column, '<=', $max);
            }
        }
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('published_at')->orderBy('id'),
            'price_low' => $query->orderBy('price')->orderByDesc('id'),
            'price_high' => $query->orderByDesc('price')->orderByDesc('id'),
            'mileage_low' => $query->orderBy('mileage')->orderByDesc('id'),
            'mileage_high' => $query->orderByDesc('mileage')->orderByDesc('id'),
            'year_new' => $query->orderByDesc('year')->orderByDesc('id'),
            'year_old' => $query->orderBy('year')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }
}
