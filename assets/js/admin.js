/**
 * Elementor CSS Framework - Admin JavaScript
 */
(function($) {
    'use strict';

    // DOM ready
    $(document).ready(function() {
        // Variables
        const $form = $('#ecf-variables-form');
        const $saveBtn = $('#ecf-save-variables, #ecf-save-variables-bottom');
        const $messages = $('#ecf-messages');
        const $searchInput = $('#ecf-search-variables');
        const $variableRows = $('.ecf-variable-row');

        // Search functionality
        $searchInput.on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $variableRows.each(function() {
                const $row = $(this);
                const variableName = $row.find('label').text().toLowerCase();
                const variableDesc = $row.find('.ecf-variable-description').text().toLowerCase();
                
                if (variableName.includes(searchTerm) || variableDesc.includes(searchTerm)) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        });

        // Save variables via AJAX
        $saveBtn.on('click', function(e) {
            e.preventDefault();
            
            // Show loading state
            $(this).prop('disabled', true).text('Saving...');
            $messages.html('<div class="notice notice-info inline"><p>Saving variables...</p></div>');
            
            // Collect all variable values
            const variables = {};
            $form.find('input[name^="variables"]').each(function() {
                const name = $(this).attr('id');
                const value = $(this).val();
                variables[name] = value;
            });
            
            // Send AJAX request
            wp.ajax.post('ecf_save_variables', {
                nonce: ecfData.nonce,
                variables: variables
            }).done(function(response) {
                // Show success message
                $messages.html('<div class="notice notice-success inline"><p>' + response.message + '</p></div>');
                
                // Reset button state
                $saveBtn.prop('disabled', false).text('Save Changes');
                
                // Fade out the message after 3 seconds
                setTimeout(function() {
                    $messages.find('.notice').fadeOut();
                }, 3000);
            }).fail(function(response) {
                // Show error message
                $messages.html('<div class="notice notice-error inline"><p>' + response.message + '</p></div>');
                
                // Reset button state
                $saveBtn.prop('disabled', false).text('Save Changes');
            });
        });
    });
})(jQuery);