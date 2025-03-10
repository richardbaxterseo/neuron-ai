<?php
/**
 * Settings AJAX handlers class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Settings
 */

namespace NeuronAI\Settings;

use NeuronAI\Providers\ProviderConstants;

/**
 * AJAX class for settings.
 *
 * Handles AJAX requests for settings.
 */
class Ajax {

    /**
     * The parent settings class.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Settings    $parent    The parent settings class.
     */
    private $parent;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     * @param    \NeuronAI\Settings    $parent    The parent settings class.
     */
    public function __construct($parent) {
        $this->parent = $parent;
    }

    /**
     * AJAX handler for testing API connections.
     *
     * @since    1.0.0
     */
    public function test_connection() {
        // Check nonce
        if (!check_ajax_referer('neuron_ai_admin', '_wpnonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token.', 'neuron-ai')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'neuron-ai')]);
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        
        if (!in_array($provider, [ProviderConstants::PROVIDER_CLAUDE, ProviderConstants::PROVIDER_GEMINI])) {
            wp_send_json_error(['message' => __('Invalid provider specified.', 'neuron-ai')]);
        }
        
        // Get container and provider factory
        $container = $this->parent->getContainer();

        if (!$container) {
            wp_send_json_error(['message' => __('Container not initialized.', 'neuron-ai')]);
        }

$provider_factory = $container->get('provider_factory');
if (!$provider_factory) {
    wp_send_json_error(['message' => __('Provider factory not initialized.', 'neuron-ai')]);
}
        
        try {
            $api_key = get_option('neuron_ai_' . $provider . '_key', '');
            $model = get_option('neuron_ai_' . $provider . '_model', ProviderConstants::getDefaultModel($provider));
            
            if (empty($api_key)) {
                wp_send_json_error([
                    'message' => __('API key not configured.', 'neuron-ai'),
                    'provider' => $provider
                ]);
            }
            
            $provider_instance = $provider_factory->getProvider($provider, $api_key, $model);
            $result = $provider_instance->testConnection();
            
            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'provider' => $provider,
                    'model' => $result['model'] ?? $model
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result['message'],
                    'provider' => $provider
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'provider' => $provider
            ]);
        }
    }

    /**
     * AJAX handler for clearing the cache.
     *
     * @since    1.0.0
     */
    public function clear_cache() {
        // Check nonce
        if (!check_ajax_referer('neuron_ai_admin', '_wpnonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token.', 'neuron-ai')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'neuron-ai')]);
        }
        
        // Clear transients with our prefix
        global $wpdb;
        $count = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_neuron_ai_%'
            )
        );
        
        $count += $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_timeout_neuron_ai_%'
            )
        );
        
        wp_send_json_success([
            'message' => sprintf(
                __('Cache cleared successfully. %d items removed.', 'neuron-ai'),
                $count
            )
        ]);
    }
}