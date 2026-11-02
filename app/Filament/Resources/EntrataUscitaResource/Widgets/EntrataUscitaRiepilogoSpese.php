<?php

namespace App\Filament\Resources\EntrataUscitaResource\Widgets;

use App\Filament\Resources\EntrataUscitaResource\Pages\ManageEntrataUscitas;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EntrataUscitaRiepilogoSpese extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected ?string $heading = 'Ripartizione Uscite per Causale';
    protected ?string $description = 'Totale uscite filtrato per le principali voci di spesa';

    protected function getTablePage(): string
    {
        return ManageEntrataUscitas::class;
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        // Considera solo le Uscite ('U')
        $usciteQuery = (clone $query)->where('tipoOperazione', 'U');
        $totaleUscite = (float)(clone $usciteQuery)->sum('importo');

        // 1. Google ADS
        $googleAdsQuery = (clone $usciteQuery)->where('causale', 'LIKE', '%google%');
        $googleAds = (float)(clone $googleAdsQuery)->sum('importo');
        $googleCount = (int)(clone $googleAdsQuery)->count();

        // 2. Facebook ADS / Meta
        $facebookAdsQuery = (clone $usciteQuery)->where(function ($q) {
            $q->where('causale', 'LIKE', '%facebook%')
              ->orWhere('causale', 'LIKE', '%meta%')
              ->orWhere('causale', 'LIKE', '%fb%');
        });
        $facebookAds = (float)(clone $facebookAdsQuery)->sum('importo');
        $facebookCount = (int)(clone $facebookAdsQuery)->count();

        // 3. TikTok ADS
        $tiktokAdsQuery = (clone $usciteQuery)->where(function ($q) {
            $q->where('causale', 'LIKE', '%tiktok%')
              ->orWhere('causale', 'LIKE', '%tik tok%');
        });
        $tiktokAds = (float)(clone $tiktokAdsQuery)->sum('importo');
        $tiktokCount = (int)(clone $tiktokAdsQuery)->count();

        // 4. Rimborsi
        $rimborsiQuery = (clone $usciteQuery)->where('causale', 'LIKE', '%rimbors%');
        $rimborsi = (float)(clone $rimborsiQuery)->sum('importo');
        $rimborsiCount = (int)(clone $rimborsiQuery)->count();

        // 5. Straordinari
        $straordinariQuery = (clone $usciteQuery)->where('causale', 'LIKE', '%straordinar%');
        $straordinari = (float)(clone $straordinariQuery)->sum('importo');
        $straordinariCount = (int)(clone $straordinariQuery)->count();

        // 6. Commissioni
        $commissioniQuery = (clone $usciteQuery)->where('causale', 'LIKE', '%commission%');
        $commissioni = (float)(clone $commissioniQuery)->sum('importo');
        $commissioniCount = (int)(clone $commissioniQuery)->count();

        // 7. Rinnovi Domini
        $rinnoviQuery = (clone $usciteQuery)->where(function ($q) {
            $q->where('causale', 'LIKE', '%rinnov%')
              ->orWhere('causale', 'LIKE', '%domini%');
        });
        $rinnovi = (float)(clone $rinnoviQuery)->sum('importo');
        $rinnoviCount = (int)(clone $rinnoviQuery)->count();

        // 8. Provvigioni PR
        $provvigioniPrQuery = (clone $usciteQuery)->where(function ($q) {
            $q->where('causale', 'LIKE', '%provvigion%')
              ->orWhere('causale', 'LIKE', 'pr %')
              ->orWhere('causale', 'LIKE', '% pr %')
              ->orWhere('causale', 'LIKE', '% pr');
        });
        $provvigioniPr = (float)(clone $provvigioniPrQuery)->sum('importo');
        $provvigioniPrCount = (int)(clone $provvigioniPrQuery)->count();

        // 9. Altre Uscite
        $altreSpeseQuery = (clone $usciteQuery)
            ->where('causale', 'NOT LIKE', '%google%')
            ->where('causale', 'NOT LIKE', '%facebook%')
            ->where('causale', 'NOT LIKE', '%meta%')
            ->where('causale', 'NOT LIKE', '%fb%')
            ->where('causale', 'NOT LIKE', '%tiktok%')
            ->where('causale', 'NOT LIKE', '%tik tok%')
            ->where('causale', 'NOT LIKE', '%provvigion%')
            ->where('causale', 'NOT LIKE', 'pr %')
            ->where('causale', 'NOT LIKE', '% pr %')
            ->where('causale', 'NOT LIKE', '% pr')
            ->where('causale', 'NOT LIKE', '%rimbors%')
            ->where('causale', 'NOT LIKE', '%straordinar%')
            ->where('causale', 'NOT LIKE', '%commission%')
            ->where('causale', 'NOT LIKE', '%rinnov%')
            ->where('causale', 'NOT LIKE', '%domini%');
        $altreSpese = (float)(clone $altreSpeseQuery)->sum('importo');
        $altreCount = (int)(clone $altreSpeseQuery)->count();

        $calcDesc = function ($amount, $count, $total) {
            if ($count === 0 || $amount <= 0) return 'Nessuna spesa';
            $perc = $total > 0 ? round(($amount / $total) * 100, 1) : 0;
            $mov = $count === 1 ? '1 spesa' : "{$count} spese";
            return "{$mov} ({$perc}% delle uscite)";
        };

        return [
            Stat::make('Google ADS', '€ ' . number_format($googleAds, 2, ',', '.'))
                ->description($calcDesc($googleAds, $googleCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),

            Stat::make('Facebook ADS', '€ ' . number_format($facebookAds, 2, ',', '.'))
                ->description($calcDesc($facebookAds, $facebookCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('TikTok ADS', '€ ' . number_format($tiktokAds, 2, ',', '.'))
                ->description($calcDesc($tiktokAds, $tiktokCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('info'),

            Stat::make('Rimborsi', '€ ' . number_format($rimborsi, 2, ',', '.'))
                ->description($calcDesc($rimborsi, $rimborsiCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('danger'),

            Stat::make('Straordinari', '€ ' . number_format($straordinari, 2, ',', '.'))
                ->description($calcDesc($straordinari, $straordinariCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Commissioni', '€ ' . number_format($commissioni, 2, ',', '.'))
                ->description($calcDesc($commissioni, $commissioniCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),

            Stat::make('Rinnovi Domini', '€ ' . number_format($rinnovi, 2, ',', '.'))
                ->description($calcDesc($rinnovi, $rinnoviCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-globe-americas')
                ->color('info'),

            Stat::make('Provvigioni PR', '€ ' . number_format($provvigioniPr, 2, ',', '.'))
                ->description($calcDesc($provvigioniPr, $provvigioniPrCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('Altre Uscite', '€ ' . number_format($altreSpese, 2, ',', '.'))
                ->description($calcDesc($altreSpese, $altreCount, $totaleUscite))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('gray'),
        ];
    }
}
