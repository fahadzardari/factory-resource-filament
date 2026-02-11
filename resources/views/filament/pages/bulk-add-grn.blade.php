<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Instructions -->
        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950 dark:to-cyan-950 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
                <span class="mr-2">📦</span> Quick Guide
            </h3>
            <ul class="space-y-1 text-blue-800 dark:text-blue-200 text-sm">
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Add multiple Goods Receipts at once — no manual entries needed!</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Only fill the rows you need:</strong> Supplier, Resource, Quantity, Unit Price</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Empty rows are automatically ignored</strong> — leave blank rows empty, they won't be validated</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Inventory updates automatically when GRNs are created</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Click "Create All GRNs" to process only the filled rows</span>
                </li>
            </ul>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <form wire:submit="submit">
                {{ $this->form }}

                <div class="mt-6 flex items-center gap-3">
                    <x-filament::button 
                        type="submit"
                        wire:loading.attr="disabled"
                        size="lg"
                        color="success"
                    >
                        <span wire:loading.remove>✅ Create All GRNs</span>
                        <span wire:loading>Processing...</span>
                    </x-filament::button>
                    
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Tip: Use clone button to duplicate similar GRNs
                    </span>
                </div>
            </form>
    </div>
</x-filament-panels::page>
