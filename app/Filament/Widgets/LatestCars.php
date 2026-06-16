<?php

namespace App\Filament\Widgets;

use App\Enums\CarStatus;
use App\Filament\Resources\CarResource;
use App\Models\Car;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestCars extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return __('admin.widgets.latest_cars');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Car::query()->with('displayImage')->latest()->limit(5))
            ->paginated(false)
            ->columns([
                Tables\Columns\ImageColumn::make('displayImage.image_path')
                    ->label('')
                    ->getStateUsing(fn (Car $record) => $record->displayImage?->url)
                    ->square()
                    ->size(48),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('cars.fields.title'))
                    ->limit(32)
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('brand')
                    ->label(__('cars.fields.brand'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('cars.fields.price'))
                    ->money(fn (Car $record) => $record->currency)
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('cars.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (CarStatus $state): string => $state->label())
                    ->color(fn (CarStatus $state): string => match ($state) {
                        CarStatus::Available => 'success',
                        CarStatus::Sold => 'warning',
                        CarStatus::Hidden => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('cars.fields.created_at'))
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label(__('admin.actions.edit'))
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Car $record): string => CarResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
