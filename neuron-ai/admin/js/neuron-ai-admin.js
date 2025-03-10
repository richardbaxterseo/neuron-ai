/**
 * JavaScript for Neuron AI admin interface
 */
(function($) {
    'use strict';

    /**
     * Initialize admin functionality
     */
    function init() {
        // Initialize API key mask/unmask functionality
        initApiKeyFields();
        
        // Initialize connection testing
        initConnectionTesting();
    }

    /**
     * Initialize API key fields with mask/unmask functionality
     */
    function initApiKeyFields() {
        const $apiKeyFields = $('#neuron_ai_anthropic_key, #neuron_ai_gemini_key');
        
        // Add show/hide toggle for API keys
        $apiKeyFields.each(function() {
            const $field = $(this);
            const $wrapper = $field.parent();
            
            // Add toggle button
            const $toggleBtn = $('<button>', {
                type: 'button',
                class: 'button neuron-ai-toggle-key',
                text: 'Show',
                css: {
                    marginLeft: '10px'
                }
            }).on('click', function(e) {
                e.preventDefault();
                
                if ($field.attr('type') === 'password') {
                    $field.attr('type', 'text');
                    $(this).text('Hide');
                } else {
                    $field.attr('type', 'password');
                    $(this).text('Show');
                }
            });
            
            $field.after($toggleBtn);
        });
    }

    /**
     * Initialize connection testing functionality
     */
    function initConnectionTesting() {
        const $anthropicSection = $('#neuron_ai_anthropic_key').closest('tr');
        const $geminiSection = $('#neuron_ai_gemini_key').closest('tr');
        
        // Add test button for Claude
        const $claudeTestBtn = $('<button>', {
            type: 'button',
            class: 'button neuron-ai-test-connection',
            text: 'Test Connection',
            css: {
                marginLeft: '10px'
            }
        }).on('click', function(e) {
            e.preventDefault();
            testConnection('claude');
        });
        
        // Add test button for Gemini
        const $geminiTestBtn = $('<button>', {
            type: 'button',
            class: 'button neuron-ai-test-connection',
            text: 'Test Connection',
            css: {
                marginLeft: '10px'
            }
        }).on('click', function(e) {
            e.preventDefault();
            testConnection('gemini');
        });
        
        // Add buttons to the page
        $anthropicSection.find('.neuron-ai-toggle-key').after($claudeTestBtn);
        $geminiSection.find('.neuron-ai-toggle-key').after($geminiTestBtn);
    }

    /**
     * Test connection to provider
     * 
     * @param {string} provider The provider to test connection to
     */
    function testConnection(provider) {
        const $button = provider === 'claude' 
            ? $('#neuron_ai_anthropic_key').closest('tr').find('.neuron-ai-test-connection')
            : $('#neuron_ai_gemini_key').closest('tr').find('.neuron-ai-test-connection');
        
        const originalText = $button.text();
        $button.text('Testing...').prop('disabled', true);
        
        // Remove any existing status message
        $(`.neuron-ai-connection-status.${provider}`).remove();
        
        // Get the API key
        const apiKey = provider === 'claude' 
            ? $('#neuron_ai_anthropic_key').val()
            : $('#neuron_ai_gemini_key').val();
        
        // Get the model
        const model = provider === 'claude' 
            ? $('#neuron_ai_claude_model').val()
            : $('#neuron_ai_gemini_model').val();
        
        if (!apiKey) {
            showConnectionResult($button, provider, false, 'API key is required');
            return;
        }
        
        // Make REST API request to test connection
        $.ajax({
            url: `/wp-json/neuron-ai/v1/providers/status`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
            },
            success: function(response) {
                const providerStatus = response.status && response.status[provider];
                
                if (providerStatus) {
                    showConnectionResult(
                        $button, 
                        provider, 
                        providerStatus.connected, 
                        providerStatus.message || (providerStatus.connected ? 'Connection successful' : 'Connection failed')
                    );
                } else {
                    showConnectionResult($button, provider, false, 'Could not get provider status');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Connection test failed';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                showConnectionResult($button, provider, false, errorMessage);
            }
        });
    }

    /**
     * Show connection test result
     * 
     * @param {jQuery} $button The button element
     * @param {string} provider The provider name
     * @param {boolean} success Whether the test was successful
     * @param {string} message The result message
     */
    function showConnectionResult($button, provider, success, message) {
        $button.text('Test Connection').prop('disabled', false);
        
        // Create status message
        const $status = $('<div>', {
            class: `neuron-ai-connection-status ${provider} ${success ? 'success' : 'error'}`,
            text: message,
            css: {
                marginTop: '10px',
                padding: '8px 12px',
                borderRadius: '4px',
                backgroundColor: success ? '#d4edda' : '#f8d7da',
                color: success ? '#155724' : '#721c24'
            }
        });
        
        // Add to the page
        $button.closest('td').append($status);
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);