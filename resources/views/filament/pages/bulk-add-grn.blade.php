<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Instructions -->
        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950 dark:to-cyan-950 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
                <span class="mr-2">📦</span> Quick Guide
            </h3>
            <ul class="space-y-1 text-blue-800 dark:text-blue-200 text-sm">
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Add multiple Goods Receipts at once — no manual entries needed!</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Only fill the rows you need:</strong> Supplier, Resource, Quantity, Unit Price</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Empty rows are automatically ignored</strong> — leave blank rows empty, they won't be validated</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Inventory updates automatically when GRNs are created</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Keyboard Navigation:</strong> Press <kbd class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded text-sm">Ctrl+Enter</kbd> to add a new row from anywhere</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Click "Create All GRNs" to process only the filled rows</span>
                </li>
            </ul>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <form wire:submit="submit">
                {{ $this->form }}

                <div class="mt-6 flex items-center gap-3">
                    <x-filament::button 
                        type="submit"
                        wire:loading.attr="disabled"
                        size="lg"
                        color="success"
                    >
                        <span wire:loading.remove>✅ Create All GRNs</span>
                        <span wire:loading>Processing...</span>
                    </x-filament::button>
                    
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Tip: Use clone button to duplicate similar GRNs
                    </span>
                </div>
            </form>
    </div>
</x-filament-panels::page>

<script>
(function() {
    'use strict';
    
    // Track repeater mutations to auto-focus new items
    function initBulkGrnKeyboardNav() {
        const repeaterContainer = document.querySelector('[data-repeater-key="grns"]')?.closest('[x-data]');
        
        if (!repeaterContainer) {
            // Try again in 500ms if not found yet
            setTimeout(initBulkGrnKeyboardNav, 500);
            return;
        }

        // Handle Ctrl+Enter to add new row
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                
                // Find and click the add button
                const addButton = repeaterContainer.querySelector('[x-on\\:click*="addItem"], button[aria-label*="add"], [x-cloak] ~ button');
                if (!addButton) {
                    // Try alternative selector
                    const buttons = repeaterContainer.querySelectorAll('button');
                    const addBtn = Array.from(buttons).find(btn => 
                        btn.textContent.includes('Add') || 
                        btn.getAttribute('aria-label')?.includes('add')
                    );
                    if (addBtn) {
                        addBtn.click();
                        setTimeout(() => focusFirstFieldOfLastRow(), 150);
                    }
                } else {
                    addButton.click();
                    setTimeout(() => focusFirstFieldOfLastRow(), 150);
                }
            }
        });

        // Use MutationObserver to detect new repeater items and auto-focus first field
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        // Check if this is a repeater item
                        if (node.nodeType === 1 && node.getAttribute?.('[x-data*="makeRepeaterItemData"]') !== null) {
                            focusFirstFieldOfItem(node);
                        }
                    });
                }
            });
        });

        // Start observing the repeater container
        observer.observe(repeaterContainer, {
            childList: true,
            subtree: true,
            attributes: false
        });

        // Also handle when form is updated via Livewire
        window.addEventListener('livewire:update', () => {
            setTimeout(() => {
                const lastRow = repeaterContainer?.querySelector('[x-data*="makeRepeaterItemData"]');
                if (lastRow) {
                    focusFirstFieldOfItem(lastRow);
                }
            }, 100);
        });
    }

    function focusFirstFieldOfLastRow() {
        const lastRow = document.querySelector('[x-data*="makeRepeaterItemData"]');
        if (lastRow) {
            focusFirstFieldOfItem(lastRow);
        }
    }

    function focusFirstFieldOfItem(item) {
        // Find select inputs first (for Supplier field), then text inputs
        const selects = item.querySelectorAll('select');
        const inputs = item.querySelectorAll('input[type="text"], input:not([type="hidden"])');
        
        const firstField = selects[0] || inputs[0];
        
        if (firstField) {
            setTimeout(() => {
                firstField.focus();
                // For select elements, try to open if they're Filament selects
                if (firstField.tagName === 'SELECT' && firstField.parentElement) {
                    const triggerButton = firstField.nextElementSibling?.querySelector('[role="button"]');
                    if (triggerButton) {
                        triggerButton.click();
                    }
                }
            }, 50);
        }
    }

    // Wait for page to be fully loaded and Livewire to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBulkGrnKeyboardNav);
    } else {
        initBulkGrnKeyboardNav();
    }

    // Also initialize when Livewire navigation happens
    window.addEventListener('livewire:navigated', initBulkGrnKeyboardNav);
})();
</script>

<style>
kbd {
    display: inline-block;
    padding: 3px 5px;
    font-size: 11px;
    border-radius: 3px;
    border: 1px solid #ccc;
    background: linear-gradient(to bottom, #f5f5f5, #f1f1f1);
    box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.25), 0 1px 0 rgba(0, 0, 0, 0.25);
}
</style>
