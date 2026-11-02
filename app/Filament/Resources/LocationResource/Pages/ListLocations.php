<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use App\Models\Location;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    public function getTitle(): string
    {
        $count = Location::count();
        return "Locations ({$count})";
    }

    public function getHeading(): string
    {
        $count = Location::count();
        return "Locations ({$count})";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('disattiva_tutte_locations')
                ->label('Disattiva tutte le Location')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Disattiva tutte le Location')
                ->modalDescription('Sei sicuro di voler disattivare tutte le location attualmente attive? Non saranno più visibili sul sito finché non verranno riattivate.')
                ->modalSubmitActionLabel('Sì, disattiva tutte')
                ->action(function (): void {
                    $updated = Location::query()
                        ->where('attivaLocation', '!=', 0)
                        ->update(['attivaLocation' => 0]);

                    Notification::make()
                        ->title('Location disattivate')
                        ->body($updated > 0
                            ? "Sono state disattivate {$updated} location."
                            : 'Tutte le location erano già disattivate.')
                        ->warning()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
