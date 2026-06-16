<?php

namespace App\Http\Resources\Api;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Car */
class CarDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'locale' => app()->getLocale(),

            // Free-text fields stored once, returned as-is.
            'title' => $this->title,
            'description' => $this->description,

            'brand' => $this->brand,
            'model' => $this->model,
            'trim' => $this->trim,

            'body_type' => $this->body_type?->value,
            'body_type_label' => $this->body_type?->label(),

            'year' => $this->year,

            'color' => $this->color?->value,
            'color_label' => $this->color?->label(),

            'price' => (float) $this->price,
            'currency' => $this->currency,

            'origin' => $this->origin?->value,
            'origin_label' => $this->origin?->label(),

            'mileage' => $this->mileage,

            'transmission' => $this->transmission?->value,
            'transmission_label' => $this->transmission?->label(),

            'fuel_type' => $this->fuel_type?->value,
            'fuel_type_label' => $this->fuel_type?->label(),

            'engine_size' => $this->engine_size,

            'drivetrain' => $this->drivetrain?->value,
            'drivetrain_label' => $this->drivetrain?->label(),

            'condition' => $this->condition?->value,
            'condition_label' => $this->condition?->label(),

            'city' => $this->city?->value,
            'city_label' => $this->city?->label(),

            'status' => $this->status?->value,
            'is_featured' => $this->is_featured,

            'whatsapp_number' => $this->whatsapp_number,
            'whatsapp_link' => $this->buildWhatsAppLink(),
            'published_at' => $this->published_at?->toIso8601String(),

            'images' => CarImageResource::collection($this->whenLoaded('images')),
        ];
    }

    /**
     * Build a ready-to-use wa.me link the frontend can drop straight into
     * an <a href> without any phone-number wrangling. Returns null when no
     * number is set on the car.
     */
    protected function buildWhatsAppLink(): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->whatsapp_number);

        if ($phone === '' || $phone === null) {
            return null;
        }

        $title = (string) $this->title;
        $message = app()->isLocale('ar')
            ? "السلام عليكم، أرغب بالاستفسار عن {$title}."
            : "Hi, I'm interested in your {$title}.";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
