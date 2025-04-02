/**
 * Elementor CSS Framework - Editor integration
 * This script ensures classes are properly reflected in the Elementor editor
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Check if we're in Elementor editor
        if (typeof elementor === 'undefined') {
            return;
        }
        
        // Wait for editor to be ready
        elementor.on('preview:loaded', function() {
            // Monitor element changes
            addEditorChangeHooks();
        });
    });
    
    /**
     * Add hooks to monitor Elementor editor changes
     */
    function addEditorChangeHooks() {
        // Listen for control changes
        elementor.channels.editor.on('change', function(view, controlName) {
            if (controlName === 'ecf_section_classes') {
                applyClassesToElement(view.container.model);
            }
        });
        
        // Initial scan for elements with our classes
        setTimeout(scanForElements, 500);
        
        // Also scan when sections panel is opened
        elementor.getPanelView().on('set:page:editor', function() {
            setTimeout(scanForElements, 300);
        });
        
        // And scan when switching between elements
        elementor.channels.editor.on('section:activated', function() {
            setTimeout(scanForElements, 300);
        });
    }
    
    /**
     * Scan for all elements and apply classes
     */
    function scanForElements() {
        // Get all sections and containers
        var $elements = elementor.$preview.contents().find('.elementor-section, .elementor-container');
        
        $elements.each(function() {
            var $element = $(this);
            var elementId = $element.data('id');
            if (!elementId) return;
            
            // Find the element model
            var model = elementor.getElementModel(elementId);
            if (model) {
                applyClassesToElement(model);
            }
        });
    }
    
    /**
     * Apply classes to an element based on its model
     */
    function applyClassesToElement(model) {
        if (!model) return;
        
        var elementId = model.id;
        var settings = model.get('settings').attributes;
        var frameworkClasses = settings.ecf_section_classes;
        
        // Find the element in the preview
        var $element = elementor.$preview.contents().find('[data-id="' + elementId + '"]');
        if (!$element.length) return;
        
        // Remove all existing framework classes
        for (var className in window.ecfConfig.classOptions) {
            $element.removeClass(className);
        }
        
        // Add the selected classes
        if (frameworkClasses && Array.isArray(frameworkClasses)) {
            frameworkClasses.forEach(function(className) {
                $element.addClass(className);
            });
        }
    }
    
})(jQuery);