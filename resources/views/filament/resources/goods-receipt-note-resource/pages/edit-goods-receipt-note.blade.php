<x-filament-panels::page>
    {{ $this->form }}

    <script>
    (function() {
        'use strict';
        
        function initGrnKeyboardNav() {
            // Wait for Filament to initialize the form
            const repeater = document.querySelector('[data-repeater-key="lineItems"]')?.closest('[x-data]');
            
            if (!repeater) {
                // Try again in 500ms if not found yet
                setTimeout(initGrnKeyboardNav, 500);
                return;
            }

            // Handle Ctrl+Enter or Cmd+Enter to add new line item
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    // Check if we're inside a form (not in other contexts like search)
                    const activeElement = document.activeElement;
                    if (!activeElement || activeElement.tagName === 'BODY') return;
                    
                    const isInForm = activeElement.closest('form') || activeElement.closest('[role="dialog"]');
                    if (!isInForm) return;
                    
                    e.preventDefault();
                    
                    // Find the add button in the repeater
                    const addButtons = repeater.querySelectorAll('button');
                    const addButton = Array.from(addButtons).find(btn => {
                        const text = btn.textContent || '';
                        return text.includes('Add') || btn.getAttribute('aria-label')?.includes('add');
                    });
                    
                    if (addButton) {
                        addButton.click();
                        // Focus first field of new item after a short delay
                        setTimeout(() => focusFirstFieldOfLastItem(), 200);
                    }
                }
            });

            // Use MutationObserver to detect new repeater items and auto-focus first field
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach((node) => {
                            // Check if this looks like a repeater item (contains inputs/selects)
                            if (node.nodeType === 1) {
                                const hasFormElements = node.querySelector('input, select, textarea');
                                if (hasFormElements && node.closest('[data-repeater-key="lineItems"]')) {
                                    focusFirstFieldOfItem(node);
                                }
                            }
                        });
                    }
                });
            });

            // Start observing the repeater container
            observer.observe(repeater, {
                childList: true,
                subtree: true,
                attributes: false
            });

            // Also handle when form is updated via Livewire/AlpineJS
            window.addEventListener('livewire:update', () => {
                setTimeout(() => focusFirstFieldOfLastItem(), 100);
            });
        }

        function focusFirstFieldOfLastItem() {
            const repeater = document.querySelector('[data-repeater-key="lineItems"]')?.closest('[x-data]');
            if (!repeater) return;
            
            // Get all repeater items
            const items = repeater.querySelectorAll('[x-data*="makeRepeaterItemData"], > div > div');
            if (items.length === 0) return;
            
            const lastItem = items[items.length - 1];
            focusFirstFieldOfItem(lastItem);
        }

        function focusFirstFieldOfItem(item) {
            if (!item) return;
            
            // Find the first select or input field
            const selects = item.querySelectorAll('select, [role="combobox"]');
            const inputs = item.querySelectorAll('input[type="text"], input[type="number"], input:not([type="hidden"])');
            
            const firstField = selects[0] || inputs[0];
            
            if (firstField) {
                setTimeout(() => {
                    firstField.focus();
                    // For select/combobox elements, try to open the dropdown
                    if (firstField.getAttribute('role') === 'combobox' || firstField.tagName === 'SELECT') {
                        const triggerButton = firstField.nextElementSibling?.querySelector('button, [role="button"]');
                        if (triggerButton) {
                            triggerButton.click();
                        }
                    }
                }, 50);
            }
        }

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initGrnKeyboardNav);
        } else {
            initGrnKeyboardNav();
        }

        // Also initialize when navigating via Filament
        window.addEventListener('livewire:navigated', initGrnKeyboardNav);
    })();
    </script>

    <style>
    kbd {
        display: inline-block;
        padding: 2px 6px;
        font-size: 11px;
        border-radius: 3px;
        border: 1px solid #ccc;
        background: linear-gradient(to bottom, #f5f5f5, #f1f1f1);
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.25), 0 1px 0 rgba(0, 0, 0, 0.25);
        font-family: monospace;
    }
    </style>
</x-filament-panels::page>
