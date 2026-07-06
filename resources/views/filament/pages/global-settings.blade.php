<x-filament-panels::page>
    <x-filament-panels::form wire:submit="salva">
        {{ $this->form }}
        
        <x-filament-panels::form.actions
            :actions="[
                \Filament\Actions\Action::make('salva')
                    ->label('Salva Impostazioni')
                    ->submit('salva'),
            ]"
        />
    </x-filament-panels::form>
</x-filament-panels::page>
