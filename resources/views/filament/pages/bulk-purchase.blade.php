<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Instructions -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950 p-6 rounded-lg border border-green-200 dark:border-green-800">
            <h3 class="text-lg font-bold text-green-900 dark:text-green-100 mb-2 flex items-center">
                <span class="mr-2">💡</span> Quick Guide
            </h3>
            <ul class="space-y-1 text-green-800 dark:text-green-200 text-sm">
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Fill in 5 pre-loaded rows (or add more using the "+ Add another purchase" button)</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Empty rows are automatically skipped — no need to delete them</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>All purchases are added to Central Hub inventory</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Click "Submit" when done — form will reset with fresh rows ready for next batch</span>
                </li>
            </ul>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <form wire:submit="submit">
                {{ $this->form }}
            </form>

            <div class="mt-6 flex items-center gap-3">
                <x-filament::button 
                    wire:click="submit" 
                    wire:loading.attr="disabled"
                    size="lg"
                    color="success"
                >
                    <span wire:loading.remove>✅ Submit All Purchases</span>
                    <span wire:loading>Processing...</span>
                </x-filament::button>
                
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Tip: You can collapse individual rows to save space
                </span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
