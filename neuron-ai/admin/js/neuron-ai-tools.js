/**
 * JavaScript for Neuron AI tools page
 */
(function($) {
    'use strict';

    /**
     * Initialize the tools functionality
     */
    function init() {
        // Set up clear cache button
        $('#neuron-ai-clear-cache').on('click', function(e) {
            e.preventDefault();
            clearCache();
        });
    }

    /**
     * Clear the plugin cache
     */
    function clearCache() {
        const $button = $('#neuron-ai-clear-cache');
        const $status = $('#neuron-ai-cache-status');
        
        // Disable button and show loading state
        $button.prop('disabled', true).text(wp.i18n.__('Clearing...', 'neuron-ai'));
        $status.hide();
        
        // Make AJAX request
        $.ajax({
            url: neuronAI.ajaxUrl,
            type: 'POST',
            data: {
                action: 'neuron_ai_clear_cache',
                _wpnonce: neuronAI.nonce
            },
            success: function(response) {
                if (response.success) {
                    showStatus($status, 'success', response.data.message);
                } else {
                    showStatus($status, 'error', response.data.message || wp.i18n.__('Error clearing cache.', 'neuron-ai'));
                }
            },
            error: function() {
                showStatus($status, 'error', wp.i18n.__('Connection error while clearing cache.', 'neuron-ai'));
            },
            complete: function() {
                // Reset button
                $button.prop('disabled', false).text(wp.i18n.__('Clear Cache', 'neuron-ai'));
            }
        });
    }

    /**
     * Show status message
     */
    function showStatus($element, type, message) {
        $element
            .removeClass('neuron-ai-status-success neuron-ai-status-error')
            .addClass('neuron-ai-status-' + type)
            .html(message)
            .show();
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $element.fadeOut();
        }, 5000);
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);