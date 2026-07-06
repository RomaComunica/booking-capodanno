<?php

namespace App\Providers\Filament;

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
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandLogo(asset('img/logo-rc-scuro.png'))
            ->favicon(asset('img/favicon-rc.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                // Statistiche page removed - redundant with Dashboard
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
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

    public function boot(): void
    {
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::user-menu.before',
            fn (): string => \Illuminate\Support\Facades\Blade::render('
                <div style="display: flex; align-items: center; gap: 6px; margin-right: 8px;">
                    <a href="/" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; border-radius: 6px; font-size: 11px; padding: 0 10px; border: 1px solid #27272a; background-color: #09090b; color: #ffffff; text-decoration: none; height: 28px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <x-heroicon-o-globe-alt style="width: 14px; height: 14px; color: #a1a1aa; flex-shrink: 0;" />
                        <span style="color: #ffffff; font-size: 11px; line-height: 1;">Visita il Sito</span>
                    </a>
                    <a href="/clear-cache" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; border-radius: 6px; font-size: 11px; padding: 0 10px; border: 1px solid #27272a; background-color: #09090b; color: #ffffff; text-decoration: none; height: 28px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <x-heroicon-o-arrow-path style="width: 14px; height: 14px; color: #a1a1aa; flex-shrink: 0;" />
                        <span style="color: #ffffff; font-size: 11px; line-height: 1;">Svuota Cache</span>
                    </a>
                </div>
            '),
        );
    }
}
