<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        // 5 featured + available
        Car::factory()
            ->count(5)
            ->available()
            ->featured()
            ->create()
            ->each(fn (Car $car) => $this->attachImages($car));

        // 18 regular available listings
        Car::factory()
            ->count(18)
            ->available()
            ->create()
            ->each(fn (Car $car) => $this->attachImages($car));

        // 4 sold (still publicly visible with "Sold" badge)
        Car::factory()
            ->count(4)
            ->sold()
            ->create()
            ->each(fn (Car $car) => $this->attachImages($car));

        // 3 hidden (admin-only, not on public site)
        Car::factory()
            ->count(3)
            ->hidden()
            ->create()
            ->each(fn (Car $car) => $this->attachImages($car));
    }

    protected function attachImages(Car $car): void
    {
        $count = random_int(4, 6);

        for ($i = 0; $i < $count; $i++) {
            $seed = Str::lower($car->slug.'-'.$i.'-'.Str::random(4));

            CarImage::factory()->create([
                'car_id' => $car->id,
                'image_path' => "https://picsum.photos/seed/{$seed}/1200/800",
                'alt_text' => "{$car->title} - photo ".($i + 1),
                'sort_order' => $i,
                'is_primary' => $i === 0,
            ]);
        }
    }
}
