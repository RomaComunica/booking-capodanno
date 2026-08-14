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
                'primary' => Color::hex('#0c5289'),
                'success' => Color::hex('#29f48f'),
                'info' => Color::hex('#DD076D'),
                'gray' => Color::Slate,
            ])
            ->darkMode(true)
            ->brandLogo(asset('img/logo-rc-scuro.png'))
            ->darkModeBrandLogo(new \Illuminate\Support\HtmlString('
                <div style="background-color: rgba(255, 255, 255, 0.95); padding: 5px 12px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.4);">
                    <img src="' . asset('img/logo-rc-scuro.png') . '" alt="Capodanno Roma" style="height: 26px; width: auto; display: block;" />
                </div>
            '))
            ->brandLogoHeight('2.5rem')
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
        // Register custom glossy design system stylesheet
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::head.done',
            fn (): string => '<link rel="stylesheet" href="' . asset('css/filament-theme-glossy.css') . '?v=' . time() . '">',
        );

        // Register topbar glossy action buttons with brighter frosted glass background fallbacks
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::user-menu.before',
            fn (): string => \Illuminate\Support\Facades\Blade::render('
                <div style="display: flex; align-items: center; gap: 8px; margin-right: 12px;">
                    <a href="/" target="_blank" class="glossy-nav-btn glossy-btn-site" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; border-radius: 20px; font-size: 12px; padding: 0 14px; height: 32px; border: 1px solid rgba(255, 255, 255, 0.3); background: rgba(255, 255, 255, 0.16); color: inherit; text-decoration: none; backdrop-filter: blur(12px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.3); transition: all 0.2s;">
                        <x-heroicon-o-globe-alt style="width: 14px; height: 14px; flex-shrink: 0; color: inherit;" />
                        <span style="font-size: 12px; line-height: 1; color: inherit; font-weight: 600;">Visita il Sito</span>
                    </a>
                    <a href="/clear-cache" class="glossy-nav-btn glossy-btn-cache" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; border-radius: 20px; font-size: 12px; padding: 0 14px; height: 32px; border: 1px solid rgba(221, 7, 109, 0.4); background: rgba(221, 7, 109, 0.18); color: inherit; text-decoration: none; backdrop-filter: blur(12px); box-shadow: 0 4px 12px rgba(221, 7, 109, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.2); transition: all 0.2s;">
                        <x-heroicon-o-arrow-path style="width: 14px; height: 14px; flex-shrink: 0; color: inherit;" />
                        <span style="font-size: 12px; line-height: 1; color: inherit; font-weight: 600;">Svuota Cache</span>
                    </a>
                </div>
            '),
        );
    }
}
