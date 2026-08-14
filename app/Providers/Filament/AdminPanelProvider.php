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
            ->brandLogo(new \Illuminate\Support\HtmlString('<div class="glossy-brand-logo"><img src="' . asset('img/logo-rc-scuro.png') . '" alt="Capodanno Roma" /></div>'))
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

        // Register topbar glossy action buttons with inline style fallbacks for Filament rendering
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::user-menu.before',
            fn (): string => \Illuminate\Support\Facades\Blade::render('
                <div style="display: flex; align-items: center; gap: 8px; margin-right: 12px;">
                    <a href="/" target="_blank" class="glossy-nav-btn glossy-btn-site" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; border-radius: 20px; font-size: 12px; padding: 0 14px; height: 32px; border: 1px solid rgba(12, 82, 137, 0.3); background-color: rgba(12, 82, 137, 0.1); color: #0c5289; text-decoration: none; backdrop-filter: blur(10px); box-shadow: 0 2px 6px rgba(12, 82, 137, 0.08); transition: all 0.2s;">
                        <x-heroicon-o-globe-alt style="width: 14px; height: 14px; flex-shrink: 0; color: inherit;" />
                        <span style="font-size: 12px; line-height: 1; color: inherit; font-weight: 600;">Visita il Sito</span>
                    </a>
                    <a href="/clear-cache" class="glossy-nav-btn glossy-btn-cache" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; border-radius: 20px; font-size: 12px; padding: 0 14px; height: 32px; border: 1px solid rgba(221, 7, 109, 0.3); background-color: rgba(221, 7, 109, 0.1); color: #DD076D; text-decoration: none; backdrop-filter: blur(10px); box-shadow: 0 2px 6px rgba(221, 7, 109, 0.08); transition: all 0.2s;">
                        <x-heroicon-o-arrow-path style="width: 14px; height: 14px; flex-shrink: 0; color: inherit;" />
                        <span style="font-size: 12px; line-height: 1; color: inherit; font-weight: 600;">Svuota Cache</span>
                    </a>
                </div>
            '),
        );
    }
}
