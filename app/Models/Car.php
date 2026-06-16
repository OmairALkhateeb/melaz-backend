<?php

namespace App\Models;

use App\Enums\BodyType;
use App\Enums\CarStatus;
use App\Enums\City;
use App\Enums\Color;
use App\Enums\Condition;
use App\Enums\Drivetrain;
use App\Enums\FuelType;
use App\Enums\Origin;
use App\Enums\Transmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Car extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'brand',
        'model',
        'trim',
        'body_type',
        'year',
        'color',
        'price',
        'currency',
        'origin',
        'mileage',
        'transmission',
        'fuel_type',
        'engine_size',
        'drivetrain',
        'condition',
        'city',
        'description',
        'whatsapp_number',
        'status',
        'is_featured',
        'published_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'status' => CarStatus::class,
        'body_type' => BodyType::class,
        'transmission' => Transmission::class,
        'fuel_type' => FuelType::class,
        'drivetrain' => Drivetrain::class,
        'condition' => Condition::class,
        'color' => Color::class,
        'origin' => Origin::class,
        'city' => City::class,
    ];

    protected $attributes = [
        'currency' => 'USD',
        'status' => 'available',
        'condition' => 'used',
        'is_featured' => false,
        'mileage' => 0,
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (self $car): void {
            if (empty($car->slug)) {
                $car->slug = static::generateUniqueSlug((string) $car->title);
            }
        });

        // Invalidate the cached filter-options payload whenever a car changes.
        $flushFilterCache = static function (): void {
            Cache::forget(config('cars.filter_options_cache_key', 'cars:filter-options'));
        };

        static::saved($flushFilterCache);
        static::deleted($flushFilterCache);
        static::restored($flushFilterCache);

        // When a car is hard-deleted, the FK cascade wipes the car_images rows
        // directly in MySQL, which bypasses Eloquent events and would leave
        // orphan files on disk. We clean up here before the cascade fires.
        static::forceDeleting(function (self $car): void {
            $car->images()->get()->each(function (CarImage $image): void {
                $image->delete();
            });

            $disk = (string) config('cars.images.disk', 'public');
            $base = trim((string) config('cars.images.directory', 'cars'), '/');
            $directory = $base.'/'.$car->getKey();

            Storage::disk($disk)->deleteDirectory($directory);
        });
    }

    protected static function generateUniqueSlug(string $title): string
    {
        // Str::slug() transliterates Arabic to ASCII, so a title typed in
        // Arabic still produces a clean URL-safe slug.
        $base = Str::slug($title, '-', 'en');
        if ($base === '') {
            $base = 'car';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(CarImage::class)->where('is_primary', true);
    }

    public function firstImage(): HasOne
    {
        return $this->hasOne(CarImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Single best image for cards/thumbnails:
     * prefer the one flagged primary, then the lowest sort_order, then lowest id.
     * Eager-loadable in one query for any number of cars.
     */
    public function displayImage(): HasOne
    {
        return $this->hasOne(CarImage::class)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', CarStatus::Available->value);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Listings the public should see.
     *
     * A car is "visible" when ALL of these are true:
     *   - status = available  (sold and hidden are excluded)
     *   - published_at is set
     *   - published_at is in the past (no scheduled/future drafts)
     *
     * This is the single source of truth used by the public API and the
     * filter-options service — keep them in sync.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->available()->published();
    }
}
