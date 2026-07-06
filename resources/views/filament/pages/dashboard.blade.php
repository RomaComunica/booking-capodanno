<x-filament-panels::page>
    <div class="mb-4">
        {{ $this->form }}
    </div>

    <x-filament-widgets::widgets
        :widgets="$this->getVisibleWidgets()"
        :columns="$this->getColumns()"
    />
</x-filament-panels::page>
