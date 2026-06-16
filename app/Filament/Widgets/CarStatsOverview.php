<?php

namespace App\Filament\Widgets;

use App\Enums\CarStatus;
use App\Models\Car;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CarStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        // Default scope excludes soft-deleted cars, which is what we want here.
        $total = Car::count();
        $available = Car::where('status', CarStatus::Available->value)->count();
        $sold = Car::where('status', CarStatus::Sold->value)->count();
        $featured = Car::where('is_featured', true)->count();
        $thisMonth = Car::where('created_at', '>=', now()->startOfMonth())->count();
        $brands = Car::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->count('brand');

        return [
            Stat::make(__('admin.stats.total'), number_format($total))
                ->description(__('admin.stats.total_desc'))
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make(__('admin.stats.available'), number_format($available))
                ->description(__('admin.stats.available_desc'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('admin.stats.sold'), number_format($sold))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make(__('admin.stats.featured'), number_format($featured))
                ->description(__('admin.stats.featured_desc'))
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make(__('admin.stats.this_month'), number_format($thisMonth))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make(__('admin.stats.brands'), number_format($brands))
                ->descriptionIcon('heroicon-m-tag')
                ->color('gray'),
        ];
    }
}
