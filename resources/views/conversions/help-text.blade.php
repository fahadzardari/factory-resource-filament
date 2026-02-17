<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex gap-3">
        <div class="flex-shrink-0 text-blue-600 text-lg">ℹ️</div>
        <div class="flex-grow">
            <h3 class="font-semibold text-blue-900 mb-2">How Unit Conversions Work</h3>
            <p class="text-blue-800 text-sm mb-3">
                Define conversion rules for this unit. When you define <strong>1 A = X B</strong>, the system automatically works in reverse too: <strong>1 B = 1/X A</strong>. You only need to define conversions in ONE direction.
            </p>
            
            <div class="bg-blue-100 rounded p-3 mb-3">
                <p class="text-xs font-semibold text-blue-900 mb-1">💡 Example:</p>
                <p class="text-xs text-blue-800">
                    If your base unit is <strong>Kilometer (km)</strong>:<br>
                    • Enter: <code class="bg-white px-1 rounded">1 km = 0.621371 miles</code><br>
                    • System automatically knows: <code class="bg-white px-1 rounded">1 mile = 1.609344 km</code><br>
                    • Both conversions work in GRN (even if you only defined one direction)
                </p>
            </div>

            <div class="bg-blue-100 rounded p-3">
                <p class="text-xs font-semibold text-blue-900 mb-1">⚠️ Important Rules:</p>
                <ul class="text-xs text-blue-800 list-disc list-inside space-y-1">
                    <li>Only add conversions between units of the <strong>same type</strong> (weight ↔ weight only)</li>
                    <li>The conversion factor = <strong>how many of the target unit equal 1 of this unit</strong></li>
                    <li>Define conversions only ONE way; the system handles reverse conversions automatically</li>
                    <li>These conversions enable flexible GRN receipt in different units with auto-conversion to base</li>
                </ul>
            </div>
        </div>
    </div>
</div>
