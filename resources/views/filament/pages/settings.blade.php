<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-check">
            Enregistrer les paramètres
        </x-filament::button>
    </form>
</x-filament-panels::page>