<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/filament-theme-glossy.css') }}?v={{ time() }}">
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}
    </x-filament-panels::form>
</x-filament-panels::page>
