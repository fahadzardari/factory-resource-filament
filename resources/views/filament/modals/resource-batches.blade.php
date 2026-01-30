<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            📋 <strong>Why batches?</strong> Each batch represents a separate purchase with its own price and date. 
            The system automatically uses the oldest batches first (FIFO) when consuming inventory.
        </p>
    </div>

    @if($batches->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-500">No batches found for this resource.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Batch #
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Purchase Date
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Original Qty
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Remaining
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Unit Cost
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Value
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Supplier
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($batches as $batch)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $batch->batch_number }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $batch->purchase_date?->format('M d, Y') ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($batch->quantity_purchased, 2) }} {{ $batch->unit_type }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($batch->quantity_remaining, 2) }} {{ $batch->unit_type }}
                                <div class="text-xs text-gray-400">
                                    {{ number_format($batch->quantity_remaining * $batch->conversion_factor, 2) }} {{ $batch->resource->unit_type }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                ${{ number_format($batch->purchase_price, 2) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-600 dark:text-green-400">
                                ${{ number_format($batch->quantity_remaining * $batch->purchase_price, 2) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $batch->supplier ?? 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-gray-100">
                            TOTAL
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($batches->sum(fn($b) => $b->quantity_remaining * $b->conversion_factor), 2) }} 
                            {{ $batches->first()->resource->unit_type }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            Avg: ${{ number_format($batches->avg('purchase_price'), 2) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400">
                            ${{ number_format($batches->sum(fn($b) => $b->quantity_remaining * $b->purchase_price), 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($batches->first()->notes)
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mt-4">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>📝 Notes:</strong> {{ $batches->first()->notes }}
                </p>
            </div>
        @endif
    @endif
</div>
