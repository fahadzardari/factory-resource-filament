<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        
        <div class="flex gap-3">
            <x-filament::button type="submit" color="success">
                ✅ Create All GRNs
            </x-filament::button>
            <x-filament::button.link color="gray" href="{{ route('filament.admin.pages.bulk-add-grn') }}">
                Cancel
            </x-filament::button.link>
        </div>
    </form>
</x-filament-panels::page>
