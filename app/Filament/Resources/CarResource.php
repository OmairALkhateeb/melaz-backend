<?php

namespace App\Filament\Resources;

use App\Enums\BodyType;
use App\Enums\Brand;
use App\Enums\CarStatus;
use App\Enums\City;
use App\Enums\Color;
use App\Enums\Condition;
use App\Enums\Drivetrain;
use App\Enums\FuelType;
use App\Enums\Origin;
use App\Enums\Transmission;
use App\Filament\Resources\CarResource\Pages;
use App\Filament\Resources\CarResource\RelationManagers\ImagesRelationManager;
use App\Models\Car;
use App\Models\CarImage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CarResource extends Resource
{
    protected static ?string $model = Car::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('cars.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cars.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.cars');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.inventory');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(12)->schema([

                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('admin.sections.listing'))
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label(__('cars.fields.title'))
                                ->placeholder(__('cars.placeholders.title'))
                                ->helperText(__('cars.helpers.title'))
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $state, Forms\Set $set, ?Car $record): void {
                                    if ($record === null) {
                                        $set('slug', Str::slug($state, '-', 'en'));
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('description')
                                ->label(__('cars.fields.description'))
                                ->helperText(__('cars.helpers.description'))
                                ->rows(5)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('slug')
                                ->label(__('cars.fields.slug'))
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->helperText(__('cars.helpers.slug'))
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Section::make(__('admin.sections.vehicle'))
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('brand')
                                    ->label(__('cars.fields.brand'))
                                    ->required()
                                    ->options(Brand::options())
                                    ->native(false)
                                    ->searchable(),

                                Forms\Components\TextInput::make('model')
                                    ->label(__('cars.fields.model'))
                                    ->required()
                                    ->maxLength(80),

                                Forms\Components\TextInput::make('trim')
                                    ->label(__('cars.fields.trim'))
                                    ->maxLength(80),

                                Forms\Components\Select::make('body_type')
                                    ->label(__('cars.fields.body_type'))
                                    ->required()
                                    ->options(BodyType::options())
                                    ->native(false)
                                    ->searchable(),

                                Forms\Components\Select::make('year')
                                    ->label(__('cars.fields.year'))
                                    ->required()
                                    ->options(static::yearOptions())
                                    ->default((int) date('Y'))
                                    ->native(false)
                                    ->searchable(),

                                Forms\Components\Select::make('condition')
                                    ->label(__('cars.fields.condition'))
                                    ->required()
                                    ->options(Condition::options())
                                    ->default(Condition::Used->value)
                                    ->native(false),

                                Forms\Components\Select::make('color')
                                    ->label(__('cars.fields.color'))
                                    ->options(Color::options())
                                    ->native(false)
                                    ->searchable(),

                                Forms\Components\Select::make('origin')
                                    ->label(__('cars.fields.origin'))
                                    ->options(Origin::options())
                                    ->native(false)
                                    ->searchable(),

                                Forms\Components\Select::make('city')
                                    ->label(__('cars.fields.city'))
                                    ->options(City::options())
                                    ->native(false)
                                    ->searchable(),
                            ]),
                        ]),

                    Forms\Components\Section::make(__('admin.sections.mechanical'))
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('transmission')
                                    ->label(__('cars.fields.transmission'))
                                    ->required()
                                    ->options(Transmission::options())
                                    ->native(false),

                                Forms\Components\Select::make('fuel_type')
                                    ->label(__('cars.fields.fuel_type'))
                                    ->required()
                                    ->options(FuelType::options())
                                    ->native(false),

                                Forms\Components\TextInput::make('engine_size')
                                    ->label(__('cars.fields.engine_size'))
                                    ->maxLength(50)
                                    ->placeholder(__('cars.placeholders.engine_size')),

                                Forms\Components\Select::make('drivetrain')
                                    ->label(__('cars.fields.drivetrain'))
                                    ->options(Drivetrain::options())
                                    ->native(false),

                                Forms\Components\TextInput::make('mileage')
                                    ->label(__('cars.fields.mileage'))
                                    ->required()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffix('km'),
                            ]),
                        ]),

                    Forms\Components\Section::make(__('admin.sections.pricing'))
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label(__('cars.fields.price'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->columnSpan(2),

                                Forms\Components\Select::make('currency')
                                    ->label(__('cars.fields.currency'))
                                    ->required()
                                    ->options([
                                        'USD' => 'USD',
                                        'EUR' => 'EUR',
                                        'GBP' => 'GBP',
                                        'AED' => 'AED',
                                        'SAR' => 'SAR',
                                        'EGP' => 'EGP',
                                        'JOD' => 'JOD',
                                        'KWD' => 'KWD',
                                        'QAR' => 'QAR',
                                        'OMR' => 'OMR',
                                        'BHD' => 'BHD',
                                    ])
                                    ->default('USD')
                                    ->native(false),
                            ]),
                        ]),
                ])->columnSpan(['lg' => 8]),

                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('admin.sections.publication'))
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label(__('cars.fields.status'))
                                ->required()
                                ->options(CarStatus::options())
                                ->default(CarStatus::Available->value)
                                ->native(false),

                            Forms\Components\Toggle::make('is_featured')
                                ->label(__('cars.fields.is_featured')),

                            Forms\Components\DateTimePicker::make('published_at')
                                ->label(__('cars.fields.published_at'))
                                ->seconds(false)
                                ->helperText(__('cars.helpers.published_at')),
                        ]),

                    Forms\Components\Section::make(__('admin.sections.contact'))
                        ->description(__('admin.contact_note.body'))
                        ->schema([
                            // The storefront routes every inquiry to one fixed
                            // WhatsApp number, so there is no per-car field.
                            Forms\Components\Placeholder::make('whatsapp_unified')
                                ->label(__('admin.contact_note.title'))
                                ->content('+963 994396648'),
                        ]),
                ])->columnSpan(['lg' => 4]),
            ]),

            // Image upload lives in the form so photos can be added while
            // CREATING a car (relation managers only show after save). On the
            // edit page the dedicated Images manager handles reordering/editing.
            Forms\Components\Section::make(__('cars.fields.images'))
                ->visibleOn('create')
                ->description(__('cars.helpers.gallery'))
                ->schema([
                    static::configuredImageUpload('primary_image')
                        ->label(__('cars.fields.primary_image'))
                        ->helperText(__('cars.helpers.primary_image'))
                        ->columnSpanFull(),

                    static::configuredImageUpload('gallery_images')
                        ->label(__('cars.fields.gallery'))
                        ->helperText(__('cars.helpers.gallery'))
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->panelLayout('grid')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * Shared FileUpload preset for car images on the create form — mirrors the
     * Images relation manager (disk, mime types, size cap, client-side resize).
     */
    protected static function configuredImageUpload(string $name): Forms\Components\FileUpload
    {
        $resize = (array) config('cars.images.resize', []);

        return Forms\Components\FileUpload::make($name)
            ->image()
            ->imageEditor()
            ->disk(CarImage::diskName())
            ->directory((string) config('cars.images.directory', 'cars'))
            ->visibility((string) config('cars.images.visibility', 'public'))
            ->acceptedFileTypes((array) config('cars.images.allowed_mimes'))
            ->maxSize((int) config('cars.images.max_size_kb', 5120))
            ->imageResizeMode($resize['mode'] ?? 'contain')
            ->imageResizeTargetWidth((string) ($resize['width'] ?? 1600))
            ->imageResizeTargetHeight((string) ($resize['height'] ?? 1067));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('displayImage.image_path')
                    ->label('')
                    ->getStateUsing(fn (Car $record) => $record->displayImage?->url)
                    ->square()
                    ->size(56)
                    ->defaultImageUrl(asset('images/car-placeholder.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('cars.fields.title'))
                    ->searchable(['title', 'brand', 'model'])
                    ->sortable()
                    ->limit(40)
                    ->description(fn (Car $record): string => trim(
                        ($record->trim ? $record->trim.' · ' : '').($record->city?->label() ?? '')
                    )),

                Tables\Columns\TextColumn::make('brand')
                    ->label(__('cars.fields.brand'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('model')
                    ->label(__('cars.fields.model'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('year')
                    ->label(__('cars.fields.year'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('cars.fields.price'))
                    ->sortable()
                    ->money(fn (Car $record) => $record->currency)
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('mileage')
                    ->label(__('cars.fields.mileage'))
                    ->sortable()
                    ->numeric()
                    ->suffix(' km')
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('cars.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (CarStatus $state): string => $state->label())
                    ->color(fn (CarStatus $state): string => match ($state) {
                        CarStatus::Available => 'success',
                        CarStatus::Sold => 'warning',
                        CarStatus::Hidden => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label(__('cars.fields.is_featured'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->label(__('cars.fields.city'))
                    ->formatStateUsing(fn (?City $state): ?string => $state?->label())
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('cars.fields.published_at'))
                    ->dateTime('M j, Y · H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('cars.fields.updated_at'))
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('cars.fields.status'))
                    ->options(CarStatus::options()),

                Tables\Filters\SelectFilter::make('brand')
                    ->label(__('cars.fields.brand'))
                    ->options(fn () => Car::query()
                        ->whereNotNull('brand')
                        ->where('brand', '!=', '')
                        ->distinct()
                        ->orderBy('brand')
                        ->pluck('brand', 'brand')
                        ->all())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('body_type')
                    ->label(__('cars.fields.body_type'))
                    ->options(BodyType::options()),

                Tables\Filters\SelectFilter::make('fuel_type')
                    ->label(__('cars.fields.fuel_type'))
                    ->options(FuelType::options()),

                Tables\Filters\SelectFilter::make('transmission')
                    ->label(__('cars.fields.transmission'))
                    ->options(Transmission::options()),

                Tables\Filters\SelectFilter::make('color')
                    ->label(__('cars.fields.color'))
                    ->options(Color::options()),

                Tables\Filters\SelectFilter::make('origin')
                    ->label(__('cars.fields.origin'))
                    ->options(Origin::options()),

                Tables\Filters\SelectFilter::make('city')
                    ->label(__('cars.fields.city'))
                    ->options(City::options()),

                Tables\Filters\Filter::make('year')
                    ->label(__('cars.fields.year'))
                    ->form([
                        Forms\Components\TextInput::make('year_min')->integer()->placeholder('From'),
                        Forms\Components\TextInput::make('year_max')->integer()->placeholder('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['year_min'] ?? null, fn ($q, $v) => $q->where('year', '>=', $v))
                            ->when($data['year_max'] ?? null, fn ($q, $v) => $q->where('year', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['year_min'] ?? null) && ! ($data['year_max'] ?? null)) {
                            return null;
                        }

                        return __('cars.fields.year').': '.($data['year_min'] ?? '…').' – '.($data['year_max'] ?? '…');
                    }),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label(__('cars.fields.is_featured')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('mark_available')
                        ->label(__('admin.actions.mark_available'))
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (Car $record) => $record->status !== CarStatus::Available)
                        ->requiresConfirmation()
                        ->action(fn (Car $record) => $record->update(['status' => CarStatus::Available->value])),

                    Tables\Actions\Action::make('mark_sold')
                        ->label(__('admin.actions.mark_sold'))
                        ->icon('heroicon-m-banknotes')
                        ->color('warning')
                        ->visible(fn (Car $record) => $record->status !== CarStatus::Sold)
                        ->requiresConfirmation()
                        ->action(fn (Car $record) => $record->update(['status' => CarStatus::Sold->value])),

                    Tables\Actions\Action::make('hide')
                        ->label(__('admin.actions.hide'))
                        ->icon('heroicon-m-eye-slash')
                        ->color('gray')
                        ->visible(fn (Car $record) => $record->status !== CarStatus::Hidden)
                        ->requiresConfirmation()
                        ->action(fn (Car $record) => $record->update(['status' => CarStatus::Hidden->value])),

                    Tables\Actions\Action::make('toggle_featured')
                        ->label(fn (Car $record) => $record->is_featured ? __('admin.actions.unfeature') : __('admin.actions.feature'))
                        ->icon(fn (Car $record) => $record->is_featured ? 'heroicon-m-star' : 'heroicon-o-star')
                        ->color('warning')
                        ->action(fn (Car $record) => $record->update(['is_featured' => ! $record->is_featured])),

                    Tables\Actions\ReplicateAction::make()
                        ->label(__('admin.actions.duplicate'))
                        ->icon('heroicon-m-document-duplicate')
                        // slug regenerates from the new title; the copy starts
                        // hidden + unpublished so it isn't public until reviewed.
                        ->excludeAttributes(['slug', 'published_at'])
                        ->beforeReplicaSaved(function (Car $replica): void {
                            $replica->title = $replica->title.__('admin.actions.copy_suffix');
                            $replica->slug = null;
                            $replica->is_featured = false;
                            $replica->status = CarStatus::Hidden->value;
                            $replica->published_at = null;
                        })
                        ->successRedirectUrl(fn (Car $replica): string => static::getUrl('edit', ['record' => $replica])),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_available')
                        ->label(__('admin.actions.mark_available'))
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => CarStatus::Available->value]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('mark_sold')
                        ->label(__('admin.actions.mark_sold'))
                        ->icon('heroicon-m-banknotes')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => CarStatus::Sold->value]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('hide')
                        ->label(__('admin.actions.hide'))
                        ->icon('heroicon-m-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => CarStatus::Hidden->value]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('feature')
                        ->label(__('admin.actions.feature'))
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('unfeature')
                        ->label(__('admin.actions.unfeature'))
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_featured' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('displayImage')
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCars::route('/'),
            'create' => Pages\CreateCar::route('/create'),
            'view' => Pages\ViewCar::route('/{record}'),
            'edit' => Pages\EditCar::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            __('cars.fields.brand') => $record->brand,
            __('cars.fields.year') => $record->year,
            __('cars.fields.status') => $record->status?->label(),
        ];
    }

    /**
     * Year dropdown options, newest year on top so most listings can be
     * created in just a few clicks.
     *
     * @return array<int, string>
     */
    protected static function yearOptions(): array
    {
        $latest = (int) date('Y') + 1;
        $earliest = 1990;
        $years = range($latest, $earliest);

        return array_combine($years, array_map('strval', $years));
    }
}
