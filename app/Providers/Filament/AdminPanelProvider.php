<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Brand name is intentionally NOT translated — the company wordmark
            // stays consistent in both Arabic and English UIs.
            // The brand mark is a dark logo that disappears on the dark panel,
            // so we show the wordmark as text instead (favicon still uses it).
            ->brandName('AL MELAZ MOTORS')
            ->favicon(asset('images/logo.png'))
            ->colors([
                // Tailwind violet ramp — matches the storefront's purple shade.
                'primary' => Color::Violet,
                'gray' => Color::Zinc,
            ])
            ->sidebarCollapsibleOnDesktop()
            // Filament v3 auto-detects RTL from the active locale (ar/fa/he/…)
            // and renders <html dir="rtl">, so we don't need to set it here.
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // Dashboard widgets (CarStatsOverview, CarsPerMonthChart, LatestCars)
            // are auto-registered via discoverWidgets above. We keep the account
            // widget but drop Filament's promo/info widget for a cleaner panel.
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                // Set locale AFTER the session is started so admins keep their
                // chosen language across requests (via the language switch).
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
