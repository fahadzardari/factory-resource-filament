/**
 * Form Keyboard Navigation Helper
 * Enables Tab key to auto-select first option in dropdowns and move focus
 */

document.addEventListener('DOMContentLoaded', function() {
    initFormKeyboardNavigation();
});

document.addEventListener('livewire:navigated', function() {
    initFormKeyboardNavigation();
});

function initFormKeyboardNavigation() {
    // Find all selects marked for auto-select on tab
    const autoSelectElements = document.querySelectorAll('[data-auto-select-on-tab="true"]');
    
    autoSelectElements.forEach(element => {
        // Look for Filament's native select component wrapper
        let selectInput = element;
        let selectElement = null;
        
        // Try to find the underlying select or Tomselect instance
        if (element.classList.contains('tomselect') || element.classList.contains('ts-control')) {
            selectInput = element;
        } else {
            // Look for a nearby select element
            selectInput = element.querySelector('input[role="combobox"]') || element.querySelector('select');
        }

        if (!selectInput) return;

        // Handle keyboard events
        selectInput.addEventListener('keydown', function(e) {
            // Check for Tab key
            if (e.key === 'Tab' || e.code === 'Tab') {
                // Don't prevent default if already has a value selected
                const currentValue = this.value;
                
                if (!currentValue || currentValue === '') {
                    // Looking for TomSelect or Filament's implementation
                    const control = this.closest('[class*="tomselect"]');
                    
                    if (control && control.tomselect) {
                        // TomSelect dropdown
                        const ts = control.tomselect;
                        const firstOption = ts.options[Object.keys(ts.options)[0]];
                        
                        if (firstOption) {
                            // Prevent default tab behavior
                            e.preventDefault();
                            
                            // Select the first option
                            ts.setValue(firstOption.value);
                            
                            // Move to next field after a brief delay
                            setTimeout(() => {
                                moveFocusToNextField(this);
                            }, 50);
                        }
                    } else if (this.tagName === 'SELECT') {
                        // Standard select
                        const options = this.querySelectorAll('option:not([disabled])');
                        if (options.length > 0) {
                            e.preventDefault();
                            this.value = options[0].value;
                            this.dispatchEvent(new Event('change', { bubbles: true }));
                            
                            setTimeout(() => {
                                moveFocusToNextField(this);
                            }, 50);
                        }
                    }
                } else {
                    // If value is already selected, just move to next field
                    moveFocusToNextField(this);
                }
            }
            // Handle Enter key as well
            else if (e.key === 'Enter' || e.code === 'Enter') {
                const currentValue = this.value;
                
                if (!currentValue || currentValue === '') {
                    const control = this.closest('[class*="tomselect"]');
                    
                    if (control && control.tomselect) {
                        const ts = control.tomselect;
                        const firstOption = ts.options[Object.keys(ts.options)[0]];
                        
                        if (firstOption) {
                            e.preventDefault();
                            ts.setValue(firstOption.value);
                            
                            setTimeout(() => {
                                moveFocusToNextField(this);
                            }, 50);
                        }
                    }
                }
            }
        });
    });
    
    // Auto-focus quantity field after item is selected
    const quantityFields = document.querySelectorAll('[data-focus-order="1"]');
    quantityFields.forEach(field => {
        // Enhanced to support numeric input fields
        field.addEventListener('focus', function(e) {
            // When quantity field gets focus, select all for easy replacement
            if (this.type === 'number' || this.type === 'text') {
                setTimeout(() => {
                    this.select();
                }, 10);
            }
        });
    });

    // Connect quantity → unit field
    quantityFields.forEach(field => {
        field.addEventListener('keydown', function(e) {
            if (e.key === 'Tab' || e.code === 'Tab') {
                // Let default behavior move to next field (unit)
                // Just ensure it focuses the unit field
            }
        });
    });
}

function moveFocusToNextField(currentElement) {
    // Find the repeater row this element belongs to
    const gridContainer = currentElement.closest('[class*="grid"]') || 
                          currentElement.closest('[class*="relative"]') ||
                          currentElement.closest('div[class*="flex"]');
    
    if (!gridContainer) return;
    
    // Find the quantity field (data-focus-order="1") in this row
    const quantityField = gridContainer.querySelector('[data-focus-order="1"]');
    
    if (quantityField) {
        setTimeout(() => {
            quantityField.focus();
            if (quantityField.type === 'number' || quantityField.type === 'text') {
                quantityField.select();
            }
        }, 50);
    }
}
