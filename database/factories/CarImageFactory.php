<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CarImage>
 */
class CarImageFactory extends Factory
{
    protected $model = CarImage::class;

    public function definition(): array
    {
        $seed = Str::lower(Str::random(10));

        return [
            'car_id' => Car::factory(),
            // Picsum gives deterministic-by-seed placeholder images.
            // The CarImage::url accessor handles both absolute URLs and storage paths.
            'image_path' => "https://picsum.photos/seed/{$seed}/1200/800",
            'alt_text' => $this->faker->sentence(4),
            'sort_order' => 0,
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => [
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }
}
