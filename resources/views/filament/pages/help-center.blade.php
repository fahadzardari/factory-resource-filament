<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
            <h2 class="text-2xl font-bold text-blue-900 dark:text-blue-100 mb-3">👋 Welcome to the Help Center!</h2>
            <p class="text-blue-700 dark:text-blue-300">
                This page contains frequently asked questions and step-by-step guides to help you use the Factory Resource Management System effectively. 
                If you're new here, start with the "Getting Started" section below.
            </p>
        </div>

        <!-- Getting Started -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <span class="mr-2">🚀</span> Getting Started
            </h3>
            
            <div class="space-y-4">
                <div class="border-l-4 border-green-500 pl-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Step 1: Understanding the System</h4>
                    <p class="text-gray-700 dark:text-gray-300">
                        This system helps you track materials (resources) across your organization. Materials are stored in two places:
                    </p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 mt-2 space-y-1">
                        <li><strong>Central Hub</strong>: Your main warehouse where you receive all purchases</li>
                        <li><strong>Project Sites</strong>: Individual construction/factory sites where materials are used</li>
                    </ul>
                </div>

                <div class="border-l-4 border-blue-500 pl-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Step 2: Your First Purchase</h4>
                    <p class="text-gray-700 dark:text-gray-300">
                        When you buy materials, record them like this:
                    </p>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-gray-400 mt-2 space-y-1">
                        <li>Go to <strong>Resources</strong> in the sidebar</li>
                        <li>Click on the material you purchased</li>
                        <li>Click the green <strong>🛒 Purchase</strong> button</li>
                        <li>Enter quantity and <strong>select purchase unit</strong> (e.g., tons, bags, liters)</li>
                        <li>System will automatically convert to base unit</li>
                        <li>Enter price per unit and supplier details</li>
                        <li>Materials are now in your Central Hub!</li>
                    </ol>
                    <div class="mt-3 bg-blue-50 dark:bg-blue-950 p-3 rounded">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>💡 Unit Conversion:</strong> You can buy cement in tons even if your base unit is kg. The system automatically converts (1 ton = 1000 kg)!
                        </p>
                    </div>
                </div>

                <div class="border-l-4 border-orange-500 pl-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Step 3: Sending to Projects</h4>
                    <p class="text-gray-700 dark:text-gray-300">
                        To send materials to a project site:
                    </p>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-gray-400 mt-2 space-y-1">
                        <li>Go to <strong>Projects</strong> and click on your project</li>
                        <li>Click the green <strong>📦 Allocate Resource</strong> button</li>
                        <li>Select the material and quantity</li>
                        <li>Materials are now at the project site!</li>
                    </ol>
                </div>

                <div class="border-l-4 border-red-500 pl-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Step 4: Recording Usage</h4>
                    <p class="text-gray-700 dark:text-gray-300">
                        When materials are used in construction:
                    </p>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-gray-400 mt-2 space-y-1">
                        <li>Open your project</li>
                        <li>Click the red <strong>🔥 Consume Resource</strong> button</li>
                        <li>Enter what was used and add notes about the work</li>
                        <li>This tracks your daily material consumption!</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Common Questions -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <span class="mr-2">❓</span> Frequently Asked Questions
            </h3>
            
            <div class="space-y-4">
                <details class="group">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                        Q: What's the difference between Allocate and Transfer?
                    </summary>
                    <div class="mt-2 p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300">
                            <strong>Allocate (📦)</strong>: Moves materials FROM Central Hub TO a project<br>
                            <strong>Transfer (🚚)</strong>: Moves materials BETWEEN projects or FROM a project BACK TO Hub
                        </p>
                    </div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                        Q: Can I undo a consumption record?
                    </summary>
                    <div class="mt-2 p-4 bg-red-50 dark:bg-red-950 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300">
                            <strong>No.</strong> Consumption is permanent because materials are physically used up. 
                            If you made a mistake, contact your system administrator to manually adjust the records.
                            Always double-check quantities before clicking "Submit"!
                        </p>
                    </div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                        Q: How do I return materials from a project back to the Hub?
                    </summary>
                    <div class="mt-2 p-4 bg-orange-50 dark:bg-orange-950 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300">
                            1. Open the project<br>
                            2. Click the orange <strong>🚚 Transfer Resource</strong> button<br>
                            3. Select the material<br>
                            4. Choose "🏢 Central Hub (Main Warehouse)" as the destination<br>
                            5. Enter quantity and submit!
                        </p>
                    </div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                        Q: What happens when I complete a project?
                    </summary>
                    <div class="mt-2 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300">
                            The system will show you all remaining materials at the project. For EACH material, you decide:<br>
                            • <strong>Return to Hub</strong>: Send back for future use (recommended)<br>
                            • <strong>Transfer to Another Project</strong>: Send directly to where it's needed<br>
                            • <strong>Keep at Project</strong>: Write-off (material stays but won't be tracked)<br>
                            After processing all materials, the project status changes to "Completed".
                        </p>
                    </div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                        Q: How do I generate reports?
                    </summary>
                    <div class="mt-2 p-4 bg-indigo-50 dark:bg-indigo-950 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300">
                            <strong>Daily Consumption Report</strong>: Shows all materials for a specific day<br>
                            • Open a project → Click "📊 Export Daily Consumption" → Select date<br><br>
                            
                            <strong>Resource Usage Report</strong>: Shows all transactions for a date range<br>
                            • Open a project → Click "📈 Export Resource Usage" → Select date range<br><br>
                            
                            <strong>Resource Transaction History</strong>: Shows all movements of a specific material<br>
                            • Open a resource → Click "📊 Export Transactions" → Select date range<br><br>
                            
                            All reports download as Excel files (.xlsx) that you can open, print, or email!
                        </p>
                    </div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                        Q: Why can't I see the action buttons on my project?
                    </summary>
                    <div class="mt-2 p-4 bg-yellow-50 dark:bg-yellow-950 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300">
                            The action buttons (Allocate, Consume, Transfer, Complete Project) only appear when the project status is <strong>"Active"</strong>.<br>
                            If your project status is "Pending" or "Completed", these buttons won't show because you shouldn't be moving materials for those projects.
                        </p>
                    </div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                        Q: What currency is used in the system?
                    </summary>
                    <div class="mt-2 p-4 bg-purple-50 dark:bg-purple-950 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300">
                            All prices and values are in <strong>AED (UAE Dirham)</strong>. Enter prices without the currency symbol - just the number.
                        </p>
                    </div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-gray-100 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                        Q: How does unit conversion work when purchasing?
                    </summary>
                    <div class="mt-2 p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                        <p class="text-gray-700 dark:text-gray-300 mb-2">
                            Each resource has a <strong>base unit</strong> (e.g., kg for cement). When purchasing, you can buy in different units and the system automatically converts:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1 mb-3">
                            <li><strong>Example 1:</strong> Resource base unit is "kg", but you buy 2 tons → System stores as 2000 kg</li>
                            <li><strong>Example 2:</strong> Resource base unit is "liters", but you buy 5 gallons → System stores as 18.93 liters</li>
                            <li><strong>Example 3:</strong> Resource base unit is "pieces", but you buy 3 dozen → System stores as 36 pieces</li>
                        </ul>
                        <p class="text-sm text-green-800 dark:text-green-200">
                            <strong>💡 Supported conversions:</strong> Weight (kg, g, tons, lb), Volume (liters, ml, gallons), Length (m, cm, ft), Count (pieces, dozen, box), and more!
                        </p>
                    </div>
                </details>
            </div>
        </div>

        <!-- Quick Tips -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950 p-6 rounded-lg border border-green-200 dark:border-green-800">
            <h3 class="text-xl font-bold text-green-900 dark:text-green-100 mb-4 flex items-center">
                <span class="mr-2">💡</span> Quick Tips
            </h3>
            
            <ul class="space-y-2 text-green-800 dark:text-green-200">
                <li class="flex items-start">
                    <span class="mr-2">✓</span>
                    <span>Always add notes when recording transactions - they help you remember details later!</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">✓</span>
                    <span>Check "Available Stock" displays before entering quantities to avoid errors</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">✓</span>
                    <span>Export reports regularly to track material usage trends</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">✓</span>
                    <span>Use the SKU codes to quickly identify materials</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">✓</span>
                    <span>Keep supplier and invoice information updated for accurate records</span>
                </li>
            </ul>
        </div>

        <!-- Import/Export Operations -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <span class="mr-2">📤📥</span> Bulk Import & Export
            </h3>
            
            <div class="space-y-4">
                <div class="border-l-4 border-purple-500 pl-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Adding Multiple Resources at Once</h4>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        Instead of creating resources one by one, you can upload an Excel or CSV file with many resources:
                    </p>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-gray-400 space-y-1">
                        <li>Go to <strong>Resources</strong> in the sidebar</li>
                        <li>Click the <strong>📥 Import Resources</strong> button at the top</li>
                        <li>Download the template file to see the required format</li>
                        <li>Fill in your resource details (name, SKU, category, unit, description)</li>
                        <li>Upload the completed file</li>
                        <li>The system will validate each row and show you any errors</li>
                        <li>Fix errors and re-upload if needed!</li>
                    </ol>
                    <div class="mt-3 bg-purple-50 dark:bg-purple-950 p-3 rounded">
                        <p class="text-sm text-purple-800 dark:text-purple-200">
                            <strong>💡 Tip:</strong> The template shows you exactly which columns are required and what format to use. Start with the template!
                        </p>
                    </div>
                </div>

                <div class="border-l-4 border-indigo-500 pl-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Adding Multiple Projects at Once</h4>
                    <p class="text-gray-700 dark:text-gray-300 mb-3">
                        You can also create many projects from a single file:
                    </p>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-gray-400 space-y-1">
                        <li>Go to <strong>Projects</strong> in the sidebar</li>
                        <li>Click the <strong>📥 Import Projects</strong> button</li>
                        <li>Download the template file</li>
                        <li>Fill in project details (name, code, location, dates, status)</li>
                        <li>Upload the file</li>
                        <li>Review any errors and fix them</li>
                    </ol>
                    <div class="mt-3 bg-indigo-50 dark:bg-indigo-950 p-3 rounded">
                        <p class="text-sm text-indigo-800 dark:text-indigo-200">
                            <strong>⚠️ Important:</strong> Project codes must be unique! If you upload a project with a code that already exists, it will be rejected.
                        </p>
                    </div>
                </div>

                <div class="border-l-4 border-teal-500 pl-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Understanding Import Errors</h4>
                    <p class="text-gray-700 dark:text-gray-300 mb-2">
                        When you upload a file, the system checks every row. If something's wrong, you'll see:
                    </p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1">
                        <li><strong>Row number</strong>: Which row has the problem</li>
                        <li><strong>Field name</strong>: Which column is wrong</li>
                        <li><strong>Error message</strong>: What needs to be fixed</li>
                    </ul>
                    <div class="mt-3 bg-teal-50 dark:bg-teal-950 p-3 rounded">
                        <p class="text-sm text-teal-800 dark:text-teal-200">
                            <strong>Common errors:</strong> Missing required fields, duplicate SKU codes, invalid units
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Need More Help -->
        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Still Need Help?</h3>
            <p class="text-gray-600 dark:text-gray-400">
                Contact your system administrator or IT support team for assistance.
            </p>
        </div>
    </div>
</x-filament-panels::page>
