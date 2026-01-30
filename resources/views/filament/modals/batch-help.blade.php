<div class="space-y-6 p-6">
    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
            🎯 What are Purchase Batches?
        </h3>
        <p class="text-blue-800 dark:text-blue-200">
            Each time you buy inventory, you create a <strong>batch</strong>. Think of it like receipts - each purchase is separate, with its own:
        </p>
        <ul class="list-disc list-inside mt-2 text-blue-800 dark:text-blue-200 space-y-1">
            <li>Quantity purchased</li>
            <li>Price per unit (can vary between batches)</li>
            <li>Unit of measurement (kg, tons, pieces, etc.)</li>
            <li>Supplier and date</li>
        </ul>
    </div>

    <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded">
        <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">
            ✅ Adding MORE Stock: Use "Record New Purchase"
        </h3>
        <p class="text-green-800 dark:text-green-200">
            <strong>When you buy more inventory:</strong>
        </p>
        <ol class="list-decimal list-inside mt-2 text-green-800 dark:text-green-200 space-y-1">
            <li>Click <strong>"🛒 Record New Purchase"</strong> button</li>
            <li>Enter what you bought (quantity, unit, price)</li>
            <li>Save - the system automatically adds it to your total stock</li>
        </ol>
        <p class="mt-3 text-green-800 dark:text-green-200 font-semibold">
            ✨ This is the MAIN way to increase inventory!
        </p>
    </div>

    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded">
        <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100 mb-2">
            🔄 FIFO: First In, First Out
        </h3>
        <p class="text-yellow-800 dark:text-yellow-200">
            When inventory is consumed (used in projects), the system automatically uses stock from the <strong>oldest batch first</strong>.
        </p>
        <p class="mt-2 text-yellow-800 dark:text-yellow-200">
            This ensures accurate costing and prevents old stock from sitting forever.
        </p>
    </div>

    <div class="bg-purple-50 dark:bg-purple-900/20 border-l-4 border-purple-500 p-4 rounded">
        <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-2">
            📊 Multi-Unit Support
        </h3>
        <p class="text-purple-800 dark:text-purple-200">
            <strong>Example:</strong> Your resource is tracked in "kg", but you can buy in different units:
        </p>
        <ul class="list-disc list-inside mt-2 text-purple-800 dark:text-purple-200 space-y-1">
            <li><strong>Batch 1:</strong> 100 kg @ $50/kg</li>
            <li><strong>Batch 2:</strong> 2 tons @ $45,000/ton (equals 2000 kg)</li>
            <li><strong>Batch 3:</strong> 500 lb @ $25/lb (different unit!)</li>
        </ul>
        <p class="mt-2 text-purple-800 dark:text-purple-200">
            All batches contribute to the same resource, just measured differently.
        </p>
    </div>

    <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded">
        <h3 class="text-lg font-semibold text-red-900 dark:text-red-100 mb-2">
            ⚠️ Editing Batches
        </h3>
        <p class="text-red-800 dark:text-red-200">
            <strong>You can edit</strong> batches to fix errors or adjust remaining quantities.
        </p>
        <p class="mt-2 text-red-800 dark:text-red-200 font-semibold">
            ❌ Limitations:
        </p>
        <ul class="list-disc list-inside mt-1 text-red-800 dark:text-red-200 space-y-1">
            <li>Cannot make remaining quantity <strong>negative</strong></li>
            <li>Cannot make remaining <strong>exceed</strong> what was purchased</li>
            <li>Cannot delete batches that have been partially used</li>
        </ul>
        <p class="mt-3 text-red-800 dark:text-red-200 text-sm italic">
            💡 If you need to add MORE stock, don't edit - create a new purchase batch instead!
        </p>
    </div>

    <div class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 p-4 rounded">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
            📝 Summary
        </h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="font-semibold text-gray-900 dark:text-gray-100">To ADD inventory:</p>
                <p class="text-gray-700 dark:text-gray-300">→ Record New Purchase</p>
            </div>
            <div>
                <p class="font-semibold text-gray-900 dark:text-gray-100">To FIX errors:</p>
                <p class="text-gray-700 dark:text-gray-300">→ Edit existing batch</p>
            </div>
            <div>
                <p class="font-semibold text-gray-900 dark:text-gray-100">Stock consumed from:</p>
                <p class="text-gray-700 dark:text-gray-300">→ Oldest batch first (FIFO)</p>
            </div>
            <div>
                <p class="font-semibold text-gray-900 dark:text-gray-100">Each batch can have:</p>
                <p class="text-gray-700 dark:text-gray-300">→ Different units & prices</p>
            </div>
        </div>
    </div>
</div>
