<x-filament-panels::page>
    <form wire:submit="changePassword">
        {{ $this->form }}
        
        <x-filament-panels::form.actions 
            :actions="[
                $this->getChangePasswordAction(),
            ]"
            :full-width="true"
        />
    </form>
</x-filament-panels::page>
