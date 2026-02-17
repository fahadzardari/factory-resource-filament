<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex gap-3">
        <div class="flex-shrink-0 text-blue-600 text-lg">ℹ️</div>
        <div class="flex-grow">
            <h3 class="font-semibold text-blue-900 mb-2">How Unit Conversions Work</h3>
            <p class="text-blue-800 text-sm mb-3">
                Define conversion rules for this unit. Each rule tells the system how to convert from <strong>{{ $fromUnitName ?? 'this unit' }} ({{ $fromUnitCode ?? 'code' }})</strong> to another unit of the same type.
            </p>
            
            <div class="bg-blue-100 rounded p-3 mb-3">
                <p class="text-xs font-semibold text-blue-900 mb-1">💡 Example:</p>
                <p class="text-xs text-blue-800">
                    If you're adding <strong>Kilogram (kg)</strong>, you might add:<br>
                    • <strong>1 kg = 1000 g</strong> (grams) - enter conversion factor as <code class="bg-white px-1 rounded">1000</code><br>
                    • <strong>1 kg = 2.205 lb</strong> (pounds) - enter conversion factor as <code class="bg-white px-1 rounded">2.205</code>
                </p>
            </div>

            <div class="bg-blue-100 rounded p-3">
                <p class="text-xs font-semibold text-blue-900 mb-1">⚠️ Important Notes:</p>
                <ul class="text-xs text-blue-800 list-disc list-inside space-y-1">
                    <li>Only add conversions to units of the <strong>same type</strong> (e.g., weight to weight)</li>
                    <li>The conversion factor = how many of the target unit equal 1 of this unit</li>
                    <li>These conversions are used throughout the system for inventory calculations</li>
                    <li>You can add multiple conversions for one unit (e.g., kg to grams, kg to pounds)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
