<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filters Section -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <form>
                {{ $this->form }}
            </form>

            <div class="mt-6 flex items-center gap-3">
                <x-filament::button 
                    wire:click="generateReport" 
                    wire:loading.attr="disabled"
                    size="lg"
                    color="primary"
                >
                    <span wire:loading.remove>📊 Generate Report</span>
                    <span wire:loading>Generating...</span>
                </x-filament::button>
            </div>
        </div>

        <!-- Report Display Section -->
        @if ($reportData !== null)
            <div class="space-y-4">
                @php
                    // All reports are now grouped by project
                    $isGroupedByProject = !empty($reportData);
                @endphp

                @if ($isGroupedByProject)
                    <!-- GROUPED REPORT: Organized by Projects -->
                    @foreach ($reportData as $projectSection)
                        @if ($projectSection['is_system_total'] ?? false)
                            <!-- SYSTEM WIDE TOTAL SECTION -->
                            <div class="bg-gradient-to-r from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900 p-6 rounded-lg border-2 border-gray-700 dark:border-gray-800">
                                <h3 class="text-2xl font-bold text-white mb-2">
                                    {{ $projectSection['project_name'] }}
                                </h3>
                                <div class="grid grid-cols-5 gap-3 mt-4">
                                    <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-300 dark:border-gray-600">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Total Opening Value</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">AED {{ number_format($projectSection['totals']['opening_value'], 2) }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-300 dark:border-gray-600">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Total In Value</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">AED {{ number_format($projectSection['totals']['in_value'], 2) }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-300 dark:border-gray-600">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Total Out Value</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">AED {{ number_format($projectSection['totals']['out_value'], 2) }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-300 dark:border-gray-600">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Total Closing Value</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">AED {{ number_format($projectSection['totals']['closing_value'], 2) }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-700 p-3 rounded border border-gray-300 dark:border-gray-600">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Report Date</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $selectedDate?->format('d-M-Y') ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- PROJECT SECTION HEADER -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-6 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                                <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-100 mb-2">
                                    📍 {{ $projectSection['project_name'] }}
                                </h3>
                                <div class="grid grid-cols-4 gap-3 mt-4">
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded border border-blue-200 dark:border-blue-700">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Opening Value</p>
                                        <p class="text-lg font-bold text-blue-900 dark:text-blue-100">AED {{ number_format($projectSection['totals']['opening_value'], 2) }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded border border-green-200 dark:border-green-700">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">In Value</p>
                                        <p class="text-lg font-bold text-green-600 dark:text-green-400">AED {{ number_format($projectSection['totals']['in_value'], 2) }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded border border-red-200 dark:border-red-700">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Out Value</p>
                                        <p class="text-lg font-bold text-red-600 dark:text-red-400">AED {{ number_format($projectSection['totals']['out_value'], 2) }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded border border-purple-200 dark:border-purple-700">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Closing Value</p>
                                        <p class="text-lg font-bold text-purple-900 dark:text-purple-100">AED {{ number_format($projectSection['totals']['closing_value'], 2) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- PROJECT ITEMS TABLE -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Item Code</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Item Description</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Unit</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Opening Qty</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">In Qty</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">In Value</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Out Qty</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Out Value</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Closing Qty</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Avg Price</th>
                                            <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Closing Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                        @forelse ($projectSection['items'] as $item)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                <td class="px-4 py-3 text-gray-900 dark:text-white font-mono text-xs">{{ $item['item_code'] }}</td>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $item['resource_name'] }}</td>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white text-xs">{{ $item['base_unit'] }}</td>
                                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($item['opening_qty'], 2) }}</td>
                                                <td class="px-4 py-3 text-right text-green-600 dark:text-green-400 font-semibold">{{ number_format($item['in_qty'], 2) }}</td>
                                                <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($item['in_value'], 2) }}</td>
                                                <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-semibold">{{ number_format($item['out_qty'], 2) }}</td>
                                                <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($item['out_value'], 2) }}</td>
                                                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 font-semibold">{{ number_format($item['closing_qty'], 2) }}</td>
                                                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">{{ number_format($item['avg_price'], 2) }}</td>
                                                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 font-semibold">{{ number_format($item['closing_value'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                                    No items for this project.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 font-semibold">
                                        <tr>
                                            <td colspan="3" class="px-4 py-3 text-gray-900 dark:text-white">{{ $projectSection['project_name'] }} - TOTALS</td>
                                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($projectSection['totals']['opening_qty'], 2) }}</td>
                                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($projectSection['totals']['in_qty'], 2) }}</td>
                                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($projectSection['totals']['in_value'], 2) }}</td>
                                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($projectSection['totals']['out_qty'], 2) }}</td>
                                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($projectSection['totals']['out_value'], 2) }}</td>
                                            <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">{{ number_format($projectSection['totals']['closing_qty'], 2) }}</td>
                                            <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">-</td>
                                            <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">{{ number_format($projectSection['totals']['closing_value'], 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    @endforeach
                @endif

                <!-- Export Options -->
                <div class="flex gap-3">
                    <x-filament::button color="info" wire:click="downloadExcel" wire:loading.attr="disabled">
                        📥 Download Excel
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="bg-blue-50 dark:bg-blue-900/20 p-8 rounded-lg border border-blue-200 dark:border-blue-800 text-center">
                <p class="text-blue-600 dark:text-blue-400 text-lg">
                    👉 Select a date and click "Generate Report" to view inventory movements
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
