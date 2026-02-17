<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex gap-3">
        <div class="flex-shrink-0 text-blue-600 text-lg">ℹ️</div>
        <div class="flex-grow">
            <h3 class="font-semibold text-blue-900 mb-2">Creating a Unit Conversion Rule</h3>
            <p class="text-blue-800 text-sm mb-3">
                Define a conversion rule: <strong>"1 [Source] = X [Target]"</strong>. The system automatically handles the reverse conversion too, so you only define it once.
            </p>
            
            <div class="bg-blue-100 rounded p-3 mb-3">
                <p class="text-xs font-semibold text-blue-900 mb-1">💡 Complete Examples (with Reciprocals):</p>
                <ul class="text-xs text-blue-800 list-disc list-inside space-y-1">
                    <li><strong>Weight:</strong> 1 kg = 1000 g (auto: 1 g = 0.001 kg)</li>
                    <li><strong>Weight:</strong> 1 mile = 1.609344 km (auto: 1 km = 0.621371 miles)</li>
                    <li><strong>Volume:</strong> 1 liter = 1000 ml (auto: 1 ml = 0.001 liter)</li>
                    <li><strong>Volume:</strong> 1 gallon = 3.78541 liters (auto: 1 liter = 0.264172 gallons)</li>
                </ul>
            </div>

            <div class="bg-green-100 rounded p-3 mb-3">
                <p class="text-xs font-semibold text-green-900 mb-1">✅ Bidirectional System:</p>
                <p class="text-xs text-green-800">
                    You only need to create conversions in <strong>ONE direction</strong>. The system automatically calculates the reverse:
                    <br><br>
                    <code class="bg-white px-1 rounded text-green-900">If you enter: 1 mile = 1.609344 km</code>
                    <br>
                    <code class="bg-white px-1 rounded text-green-900">System knows: 1 km = 1 ÷ 1.609344 = 0.621371 miles</code>
                </p>
            </div>

            <div class="bg-blue-100 rounded p-3">
                <p class="text-xs font-semibold text-blue-900 mb-1">⚠️ Important Rules:</p>
                <ul class="text-xs text-blue-800 list-disc list-inside space-y-1">
                    <li>Both units must be of the <strong>same type</strong> (e.g., both length: miles & km)</li>
                    <li>The conversion factor is: <strong>1 [from unit] = X [to unit]</strong></li>
                    <li>Use precise decimal values (e.g., 0.001, 1.609344, 3.28084)</li>
                    <li>Define conversions ONCE; reverse conversions are automatic via the system</li>
                </ul>
            </div>
        </div>
    </div>
</div>
