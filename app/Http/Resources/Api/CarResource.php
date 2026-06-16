<?php

namespace App\Http\Resources\Api;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slim card/summary payload for the public car list.
 * For full detail (description, gallery, whatsapp number, etc.)
 * see CarDetailResource.
 *
 * @mixin Car
 */
class CarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,

            // Free-text fields are stored once in whichever language the
            // admin entered. They are returned as-is.
            'title' => $this->title,

            'brand' => $this->brand,
            'model' => $this->model,
            'trim' => $this->trim,

            'body_type' => $this->body_type?->value,
            'body_type_label' => $this->body_type?->label(),
            'year' => $this->year,

            // Enumerated fields expose a stable machine value plus a label
            // already translated for the active locale.
            'color' => $this->color?->value,
            'color_label' => $this->color?->label(),

            'price' => (float) $this->price,
            'currency' => $this->currency,

            'mileage' => $this->mileage,
            'transmission' => $this->transmission?->value,
            'transmission_label' => $this->transmission?->label(),
            'fuel_type' => $this->fuel_type?->value,
            'fuel_type_label' => $this->fuel_type?->label(),
            'condition' => $this->condition?->value,
            'condition_label' => $this->condition?->label(),

            'city' => $this->city?->value,
            'city_label' => $this->city?->label(),

            'is_featured' => $this->is_featured,
            'locale' => app()->getLocale(),

            // Inlined to avoid the heavier CarImageResource payload (id,
            // sort_order, is_primary) that the frontend never uses on cards.
            'primary_image' => $this->when(
                $this->relationLoaded('displayImage'),
                fn () => $this->displayImage ? [
                    'url' => $this->displayImage->url,
                    'alt' => $this->displayImage->alt_text,
                ] : null,
            ),
        ];
    }
}
