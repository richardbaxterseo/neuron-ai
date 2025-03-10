/**
 * JavaScript for Neuron AI error handling
 */
(function($) {
    'use strict';

    /**
     * Neuron AI Error Handler
     * Provides methods for handling and displaying errors in the UI
     */
    window.NeuronAIErrorHandler = {
        
        /**
         * Initialize error handling for AJAX requests
         */
        initAjaxErrorHandling: function() {
            // Add a global AJAX error handler
            $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
                // Only handle neuron AI AJAX requests
                if (ajaxSettings.data && typeof ajaxSettings.data === 'string' && 
                    ajaxSettings.data.indexOf('neuron_ai') !== -1) {
                    
                    NeuronAIErrorHandler.handleAjaxError(jqXHR, ajaxSettings, thrownError);
                }
            });
        },
        
        /**
         * Handle AJAX errors
         */
        handleAjaxError: function(jqXHR, ajaxSettings, thrownError) {
            let errorMessage = '';
            
            try {
                // Try to parse the response as JSON
                const response = JSON.parse(jqXHR.responseText);
                
                if (response.data && response.data.message) {
                    errorMessage = response.data.message;
                }
            } catch(e) {
                // If parsing fails, use generic error message
                errorMessage = thrownError || jqXHR.statusText || wp.i18n.__('Unknown error', 'neuron-ai');
            }
            
            // Log error to console with details
            console.error('Neuron AI Error:', {
                status: jqXHR.status,
                statusText: jqXHR.statusText,
                responseText: jqXHR.responseText,
                ajaxSettings: ajaxSettings
            });
            
            // Show error notification
            this.showErrorNotification(errorMessage);
            
            // Trigger custom event that other scripts can listen for
            $(document).trigger('neuron_ai_error', [errorMessage, jqXHR]);
        },
        
        /**
         * Show error in block editor
         */
        showBlockError: function(blockElement, message, isHtml = false) {
            const $blockElement = $(blockElement);
            
            // Remove any existing error messages
            $blockElement.find('.neuron-ai-block-error').remove();
            
            // Create error element
            const $errorElement = $('<div class="neuron-ai-block-error"></div>');
            
            if (isHtml) {
                $errorElement.html(message);
            } else {
                $errorElement.text(message);
            }
            
            // Add error to block
            $blockElement.prepend($errorElement);
            
            // Auto-remove after 10 seconds
            setTimeout(function() {
                $errorElement.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 10000);
        },
        
        /**
         * Show error notification
         */
        showErrorNotification: function(message) {
            // Check if we're in the block editor
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/notices')) {
                // Use WordPress block editor notices
                wp.data.dispatch('core/notices').createErrorNotice(message, {
                    id: 'neuron-ai-error-' + Date.now(),
                    isDismissible: true
                });
            } else {
                // Fallback to jQuery notification
                this.showJQueryNotification(message);
            }
        },
        
        /**
         * Show jQuery notification
         */
        showJQueryNotification: function(message) {
            // Check if notification container exists
            let $notificationContainer = $('.neuron-ai-notifications');
            
            if (!$notificationContainer.length) {
                // Create notification container
                $notificationContainer = $('<div class="neuron-ai-notifications"></div>');
                $('body').append($notificationContainer);
            }
            
            // Create notification
            const $notification = $('<div class="neuron-ai-notification neuron-ai-notification-error"></div>');
            $notification.html('<p>' + message + '</p><button class="neuron-ai-notification-close">&times;</button>');
            
            // Add notification to container
            $notificationContainer.append($notification);
            
            // Add close handler
            $notification.find('.neuron-ai-notification-close').on('click', function() {
                $notification.fadeOut(400, function() {
                    $(this).remove();
                });
            });
            
            // Auto-remove after 10 seconds
            setTimeout(function() {
                $notification.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 10000);
        },
        
        /**
         * Format error response from API
         */
        formatErrorResponse: function(response) {
            if (!response) {
                return wp.i18n.__('Unknown error occurred.', 'neuron-ai');
            }
            
            let message = '';
            
            if (response.message) {
                message = response.message;
            } else if (response.data && response.data.message) {
                message = response.data.message;
            } else {
                message = wp.i18n.__('AI service returned an error.', 'neuron-ai');
            }
            
            // Add additional details for admins if available
            if (neuronAI && neuronAI.isAdmin && response.details) {
                let details = '';
                
                if (typeof response.details === 'string') {
                    details = response.details;
                } else {
                    try {
                        details = JSON.stringify(response.details, null, 2);
                    } catch(e) {
                        details = wp.i18n.__('Technical details unavailable.', 'neuron-ai');
                    }
                }
                
                message += '<div class="neuron-ai-error-details">';
                message += '<p><strong>' + wp.i18n.__('Technical Details:', 'neuron-ai') + '</strong></p>';
                message += '<pre>' + details + '</pre>';
                message += '</div>';
            }
            
            return message;
        },
        
        /**
         * Handle specific provider errors
         */
        handleProviderError: function(code, blockElement) {
            let message = '';
            let actionLink = '';
            
            switch (code) {
                case 1001: // ERROR_MISSING_API_KEY
                    message = wp.i18n.__('API key is missing or not configured.', 'neuron-ai');
                    actionLink = '<a href="' + neuronAI.settingsUrl + '" class="neuron-ai-error-action">' + 
                        wp.i18n.__('Configure API Keys', 'neuron-ai') + '</a>';
                    break;
                    
                case 2003: // ERROR_RATE_LIMIT_EXCEEDED
                    message = wp.i18n.__('AI service rate limit exceeded. Please try again in a few minutes.', 'neuron-ai');
                    break;
                    
                case 3001: // ERROR_CAPABILITY_NOT_SUPPORTED
                    message = wp.i18n.__('This feature is not supported by the selected AI model.', 'neuron-ai');
                    actionLink = '<a href="' + neuronAI.settingsUrl + '" class="neuron-ai-error-action">' + 
                        wp.i18n.__('Change Model', 'neuron-ai') + '</a>';
                    break;
                    
                default:
                    message = wp.i18n.__('AI service encountered an error.', 'neuron-ai');
            }
            
            if (actionLink) {
                message += ' ' + actionLink;
            }
            
            if (blockElement) {
                this.showBlockError(blockElement, message, true);
            } else {
                this.showErrorNotification(message);
            }
        }
    };
    
    // Initialize error handling when document is ready
    $(document).ready(function() {
        NeuronAIErrorHandler.initAjaxErrorHandling();
    });

})(jQuery);