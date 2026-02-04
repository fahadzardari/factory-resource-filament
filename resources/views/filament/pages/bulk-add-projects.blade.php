<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Instructions -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950 dark:to-pink-950 p-6 rounded-lg border border-purple-200 dark:border-purple-800">
            <h3 class="text-lg font-bold text-purple-900 dark:text-purple-100 mb-2 flex items-center">
                <span class="mr-2">💡</span> Quick Guide
            </h3>
            <ul class="space-y-1 text-purple-800 dark:text-purple-200 text-sm">
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Add multiple projects at once — no Excel file needed!</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Required:</strong> Name, Code, Status, Start Date</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Project code must be unique — duplicates will be skipped</span>
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
                    <span wire:loading.remove>✅ Submit All Projects</span>
                    <span wire:loading>Processing...</span>
                </x-filament::button>
                
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Tip: Use clone button to duplicate similar projects
                </span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
