<?php

namespace App\Filament\Resources\CarResource\Pages;

use App\Filament\Resources\CarResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCar extends CreateRecord
{
    protected static string $resource = CarResource::class;

    /**
     * Uploaded image paths pulled out of the form before the Car row is saved
     * (they aren't Car columns) and turned into car_images rows afterwards.
     *
     * @var array{primary: ?string, gallery: array<int, string>}
     */
    protected array $pendingImages = ['primary' => null, 'gallery' => []];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingImages = [
            'primary' => $data['primary_image'] ?? null,
            'gallery' => array_values($data['gallery_images'] ?? []),
        ];

        unset($data['primary_image'], $data['gallery_images']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var \App\Models\Car $car */
        $car = $this->record;

        $order = 0;
        $primaryAssigned = false;

        $primary = $this->pendingImages['primary'];
        if (is_string($primary) && $primary !== '') {
            $car->images()->create([
                'image_path' => $primary,
                'is_primary' => true,
                'sort_order' => $order++,
            ]);
            $primaryAssigned = true;
        }

        foreach ($this->pendingImages['gallery'] as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $car->images()->create([
                'image_path' => $path,
                // Fall back to the first gallery image as cover when no
                // dedicated primary image was uploaded.
                'is_primary' => ! $primaryAssigned,
                'sort_order' => $order++,
            ]);

            $primaryAssigned = true;
        }
    }

    protected function getRedirectUrl(): string
    {
        // After creation, jump to the edit page so the admin can fine-tune images.
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
