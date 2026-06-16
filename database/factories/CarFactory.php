<?php

namespace Database\Factories;

use App\Enums\BodyType;
use App\Enums\CarStatus;
use App\Enums\City;
use App\Enums\Color;
use App\Enums\Condition;
use App\Enums\Drivetrain;
use App\Enums\FuelType;
use App\Enums\Origin;
use App\Enums\Transmission;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    /**
     * Realistic brand => models mapping for demo data.
     *
     * @var array<string, array<int, string>>
     */
    protected array $catalog = [
        'Toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Land Cruiser', 'Hilux', 'Yaris'],
        'Honda' => ['Civic', 'Accord', 'CR-V', 'Pilot', 'HR-V'],
        'BMW' => ['3 Series', '5 Series', '7 Series', 'X3', 'X5', 'X7', 'M4'],
        'Mercedes-Benz' => ['C-Class', 'E-Class', 'S-Class', 'GLC', 'GLE', 'GLS', 'CLA'],
        'Audi' => ['A3', 'A4', 'A6', 'Q3', 'Q5', 'Q7', 'Q8'],
        'Lexus' => ['ES', 'IS', 'RX', 'NX', 'LX', 'GX'],
        'Nissan' => ['Altima', 'Maxima', 'Patrol', 'X-Trail', 'Sunny'],
        'Hyundai' => ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Accent'],
        'Kia' => ['Optima', 'Sportage', 'Sorento', 'Cerato'],
        'Ford' => ['Mustang', 'Explorer', 'F-150', 'Edge', 'Escape'],
        'Chevrolet' => ['Tahoe', 'Suburban', 'Camaro', 'Silverado', 'Malibu'],
        'Porsche' => ['911', 'Cayenne', 'Macan', 'Panamera'],
    ];

    /**
     * Arabic transliterations of the brand names, used to demo titles that
     * were typed in Arabic by an admin.
     *
     * @var array<string, string>
     */
    protected array $brandsAr = [
        'Toyota' => 'تويوتا',
        'Honda' => 'هوندا',
        'BMW' => 'بي إم دبليو',
        'Mercedes-Benz' => 'مرسيدس',
        'Audi' => 'أودي',
        'Lexus' => 'لكزس',
        'Nissan' => 'نيسان',
        'Hyundai' => 'هيونداي',
        'Kia' => 'كيا',
        'Ford' => 'فورد',
        'Chevrolet' => 'شيفروليه',
        'Porsche' => 'بورش',
    ];

    public function definition(): array
    {
        $brand = $this->faker->randomElement(array_keys($this->catalog));
        $model = $this->faker->randomElement($this->catalog[$brand]);
        $year = $this->faker->numberBetween(2015, 2025);
        $bodyType = $this->faker->randomElement(BodyType::cases())->value;
        $condition = $this->faker->randomElement(Condition::cases())->value;

        // Mix Arabic and English titles in demo data so the UI is realistic.
        $typeArabic = $this->faker->boolean(50);
        $title = $typeArabic
            ? "{$this->brandsAr[$brand]} {$model} {$year}"
            : "{$year} {$brand} {$model}";
        $slug = Str::slug("{$year} {$brand} {$model}").'-'.Str::lower(Str::random(6));

        // Price/mileage ranges roughly tied to condition.
        $price = $condition === Condition::New->value
            ? $this->faker->numberBetween(25_000, 180_000)
            : $this->faker->numberBetween(8_000, 90_000);

        $mileage = $condition === Condition::New->value
            ? $this->faker->numberBetween(0, 500)
            : $this->faker->numberBetween(5_000, 250_000);

        $isFeatured = $this->faker->boolean(20);
        $status = $this->faker->randomElement([
            CarStatus::Available->value,
            CarStatus::Available->value,
            CarStatus::Available->value,
            CarStatus::Sold->value,
        ]);

        $description = $typeArabic
            ? "سيارة {$this->brandsAr[$brand]} {$model} موديل {$year}. حالتها ممتازة، مكيّفة، ناقل حركة موثوق، صيانة دورية، ومستندات سليمة. مناسبة للاستخدام اليومي والسفر الطويل."
            : $this->faker->paragraphs(3, true);

        return [
            'title' => $title,
            'slug' => $slug,
            'brand' => $brand,
            'model' => $model,
            'trim' => $this->faker->optional(0.6)->randomElement(['Base', 'Sport', 'Luxury', 'Premium', 'Limited', 'GT', 'Touring']),
            'body_type' => $bodyType,
            'year' => $year,
            'color' => $this->faker->randomElement(Color::cases())->value,
            'price' => $price,
            'currency' => $this->faker->randomElement(['USD', 'AED', 'SAR', 'EUR']),
            'origin' => $this->faker->randomElement(Origin::cases())->value,
            'mileage' => $mileage,
            'transmission' => $this->faker->randomElement(Transmission::cases())->value,
            'fuel_type' => $this->faker->randomElement([
                FuelType::Petrol->value,
                FuelType::Petrol->value,
                FuelType::Diesel->value,
                FuelType::Hybrid->value,
                FuelType::Electric->value,
            ]),
            'engine_size' => $this->faker->randomElement(['1.5L', '1.8L', '2.0L', '2.4L', '3.0L V6', '3.5L V6', '4.0L V8', '5.0L V8']),
            'drivetrain' => $this->faker->randomElement(Drivetrain::cases())->value,
            'condition' => $condition,
            'city' => $this->faker->randomElement(City::cases())->value,
            'description' => $description,
            'whatsapp_number' => '+966'.$this->faker->numerify('5########'),
            'status' => $status,
            'is_featured' => $isFeatured,
            'published_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => ['status' => CarStatus::Available->value]);
    }

    public function sold(): static
    {
        return $this->state(fn () => ['status' => CarStatus::Sold->value]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => CarStatus::Hidden->value]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }
}
