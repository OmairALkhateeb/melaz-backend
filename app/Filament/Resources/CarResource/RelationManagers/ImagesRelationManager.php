<?php

namespace App\Filament\Resources\CarResource\RelationManagers;

use App\Models\Car;
use App\Models\CarImage;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $recordTitleAttribute = 'alt_text';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('cars.fields.images');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            $this->configuredImageUpload('image_path')
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('alt_text')
                ->label(__('cars.fields.alt_text'))
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('sort_order')
                ->label(__('cars.fields.sort_order'))
                ->integer()
                ->default(0)
                ->minValue(0),

            Forms\Components\Toggle::make('is_primary')
                ->label(__('cars.fields.is_primary'))
                ->helperText(__('cars.helpers.is_primary')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt_text')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('')
                    ->getStateUsing(fn (CarImage $record): string => $record->url)
                    ->square()
                    ->size(72),

                Tables\Columns\TextColumn::make('alt_text')
                    ->label(__('cars.fields.alt_text'))
                    ->limit(60)
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label(__('cars.fields.is_primary'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('cars.fields.sort_order'))
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('upload_many')
                    ->label(__('admin.actions.upload_images'))
                    ->icon('heroicon-m-arrow-up-tray')
                    ->form([
                        $this->configuredImageUpload('files')
                            ->label(__('cars.fields.images'))
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        /** @var Car $car */
                        $car = $this->ownerRecord;

                        $existingMax = (int) $car->images()->max('sort_order');
                        $hasPrimary = $car->images()->where('is_primary', true)->exists();

                        foreach ($data['files'] as $i => $path) {
                            $car->images()->create([
                                'image_path' => $path,
                                'sort_order' => $existingMax + $i + 1,
                                'is_primary' => ! $hasPrimary && $i === 0,
                            ]);
                        }
                    }),

                Tables\Actions\CreateAction::make()->label(__('admin.actions.add_single_image')),
            ])
            ->actions([
                Tables\Actions\Action::make('set_primary')
                    ->label(__('admin.actions.set_primary'))
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->visible(fn (CarImage $record) => ! $record->is_primary)
                    ->action(fn (CarImage $record) => $record->update(['is_primary' => true])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-photo');
    }

    /**
     * Centralized FileUpload preset for car images — single source of truth
     * for disk, directory, mime types, size cap, and client-side resize.
     */
    protected function configuredImageUpload(string $name): FileUpload
    {
        $resize = (array) config('cars.images.resize', []);

        return FileUpload::make($name)
            ->image()
            ->imageEditor()
            ->disk(CarImage::diskName())
            ->directory(fn (): string => $this->imagesDirectory())
            ->visibility((string) config('cars.images.visibility', 'public'))
            ->acceptedFileTypes((array) config('cars.images.allowed_mimes'))
            ->maxSize((int) config('cars.images.max_size_kb', 5120))
            ->imageResizeMode($resize['mode'] ?? 'contain')
            ->imageResizeTargetWidth((string) ($resize['width'] ?? 1600))
            ->imageResizeTargetHeight((string) ($resize['height'] ?? 1067))
            ->helperText('JPEG/PNG/WebP, up to '.((int) config('cars.images.max_size_kb', 5120) / 1024).' MB. Large images are auto-resized.');
    }

    protected function imagesDirectory(): string
    {
        $base = trim((string) config('cars.images.directory', 'cars'), '/');

        return $base.'/'.$this->ownerRecord->getKey();
    }
}
