<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Instructions -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
                <span class="mr-2">💡</span> Quick Guide
            </h3>
            <ul class="space-y-1 text-blue-800 dark:text-blue-200 text-sm">
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Add multiple resources at once — no Excel file needed!</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Required:</strong> Name, SKU, Category, Base Unit</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>SKU must be unique — duplicates will be skipped</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Empty rows are automatically ignored</span>
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
                    color="primary"
                >
                    <span wire:loading.remove>✅ Submit All Resources</span>
                    <span wire:loading>Processing...</span>
                </x-filament::button>
                
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Tip: Use clone button to duplicate similar resources
                </span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
