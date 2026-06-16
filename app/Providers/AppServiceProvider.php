<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force https URLs in production so reverse-proxied requests never
        // generate http:// links (which can cause mixed-content errors and
        // bypass HSTS on first visit).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureFilamentLanguageSwitch();
    }

    /**
     * Configure the language switch widget shown in the Filament topbar.
     * It writes the chosen locale to session('locale') and our SetLocale
     * middleware reads it on every subsequent request.
     */
    protected function configureFilamentLanguageSwitch(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch): void {
            $switch
                ->locales(['ar', 'en'])
                ->labels([
                    'ar' => 'العربية',
                    'en' => 'English',
                ])
                ->visible(insidePanels: true, outsidePanels: true)
                ->circular();
        });
    }
}
