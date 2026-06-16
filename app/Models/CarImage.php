<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CarImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'is_primary' => false,
        'sort_order' => 0,
    ];

    protected static function booted(): void
    {
        // Guarantee a single primary image per car.
        static::saving(function (self $image): void {
            if ($image->is_primary) {
                static::query()
                    ->where('car_id', $image->car_id)
                    ->when($image->exists, fn ($q) => $q->where('id', '!=', $image->id))
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });

        // Remove the underlying file when an image row is deleted.
        static::deleting(function (self $image): void {
            $image->deleteFile();
        });
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Resolved public URL for the image, regardless of whether the stored
     * value is a relative path on the configured disk or a full external URL.
     */
    public function getUrlAttribute(): string
    {
        $path = (string) $this->image_path;

        if ($this->isRemoteUrl($path)) {
            return $path;
        }

        return Storage::disk(self::diskName())->url($path);
    }

    /**
     * Delete the underlying file from storage without touching the DB row.
     * No-op for remote URLs (seeded demo data).
     */
    public function deleteFile(): void
    {
        $path = (string) $this->image_path;

        if ($path === '' || $this->isRemoteUrl($path)) {
            return;
        }

        Storage::disk(self::diskName())->delete($path);
    }

    public static function diskName(): string
    {
        return (string) config('cars.images.disk', 'public');
    }

    protected function isRemoteUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }
}
