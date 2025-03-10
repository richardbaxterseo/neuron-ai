<?php
/**
 * Settings API registration class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Settings
 */

namespace NeuronAI\Settings;

use NeuronAI\Providers\ProviderConstants;

/**
 * API class for settings registration.
 *
 * Handles registration of settings with WordPress Settings API.
 */
class API {

    /**
     * The parent settings class.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Settings    $parent    The parent settings class.
     */
    private $parent;

    /**
     * The settings sections.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $sections    The settings sections.
     */
    private $sections = [];

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     * @param    \NeuronAI\Settings    $parent    The parent settings class.
     */
    public function __construct($parent) {
        $this->parent = $parent;
        
        // Define sections
        $this->sections = [
            'api_keys' => __('API Keys', 'neuron-ai'),
            'general' => __('General', 'neuron-ai'),
            'content' => __('Content', 'neuron-ai'),
            'advanced' => __('Advanced', 'neuron-ai'),
        ];
    }

    /**
     * Register settings with WordPress API.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // API Keys settings
        register_setting('neuron_ai_api_keys', 'neuron_ai_anthropic_key', [
            'sanitize_callback' => [$this, 'sanitize_api_key'],
            'default' => '',
        ]);
        register_setting('neuron_ai_api_keys', 'neuron_ai_claude_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_CLAUDE),
        ]);
        register_setting('neuron_ai_api_keys', 'neuron_ai_gemini_key', [
            'sanitize_callback' => [$this, 'sanitize_api_key'],
            'default' => '',
        ]);
        register_setting('neuron_ai_api_keys', 'neuron_ai_gemini_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_GEMINI),
        ]);

        // General Settings
        register_setting('neuron_ai_general', 'neuron_ai_default_provider_enhance', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        register_setting('neuron_ai_general', 'neuron_ai_default_provider_search', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        register_setting('neuron_ai_general', 'neuron_ai_default_provider_chat', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        register_setting('neuron_ai_general', 'neuron_ai_enable_blocks', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);

        // Content Settings
        register_setting('neuron_ai_content', 'neuron_ai_default_tone', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'professional',
        ]);
        register_setting('neuron_ai_content', 'neuron_ai_default_reading_level', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'universal',
        ]);
        register_setting('neuron_ai_content', 'neuron_ai_preserve_tone', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);
        register_setting('neuron_ai_content', 'neuron_ai_default_detail_level', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'medium',
        ]);
        register_setting('neuron_ai_content', 'neuron_ai_include_citations', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);
        register_setting('neuron_ai_content', 'neuron_ai_context_range', [
            'sanitize_callback' => 'absint',
            'default' => 3,
        ]);

        // Advanced Settings
        register_setting('neuron_ai_advanced', 'neuron_ai_max_retries', [
            'sanitize_callback' => 'absint',
            'default' => 3,
        ]);
        register_setting('neuron_ai_advanced', 'neuron_ai_retry_delay', [
            'sanitize_callback' => 'absint',
            'default' => 1000,
        ]);
        register_setting('neuron_ai_advanced', 'neuron_ai_log_retention', [
            'sanitize_callback' => 'absint',
            'default' => 30,
        ]);
        register_setting('neuron_ai_advanced', 'neuron_ai_debug_mode', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false,
        ]);
        register_setting('neuron_ai_advanced', 'neuron_ai_cache_enabled', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true,
        ]);
        register_setting('neuron_ai_advanced', 'neuron_ai_cache_ttl', [
            'sanitize_callback' => 'absint',
            'default' => 3600, // 1 hour
        ]);

        $this->register_settings_sections();
    }

    /**
     * Register settings sections and fields.
     *
     * @since    1.0.0
     */
    private function register_settings_sections() {
        // API Keys section
        add_settings_section(
            'neuron_ai_api_keys_section',
            __('AI Provider API Keys', 'neuron-ai'),
            [$this, 'api_keys_section_callback'],
            'neuron_ai_api_keys'
        );

        add_settings_field(
            'neuron_ai_anthropic_key',
            __('Claude API Key', 'neuron-ai'),
            [new Fields($this->parent), 'anthropic_key_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );

        add_settings_field(
            'neuron_ai_claude_model',
            __('Claude Model', 'neuron-ai'),
            [new Fields($this->parent), 'claude_model_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );

        add_settings_field(
            'neuron_ai_gemini_key',
            __('Gemini API Key', 'neuron-ai'),
            [new Fields($this->parent), 'gemini_key_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );

        add_settings_field(
            'neuron_ai_gemini_model',
            __('Gemini Model', 'neuron-ai'),
            [new Fields($this->parent), 'gemini_model_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );
        
        add_settings_field(
            'neuron_ai_test_connection',
            __('Test Connections', 'neuron-ai'),
            [new Fields($this->parent), 'test_connection_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );

        // General Settings section
        add_settings_section(
            'neuron_ai_general_section',
            __('General Settings', 'neuron-ai'),
            [$this, 'general_section_callback'],
            'neuron_ai_general'
        );

        add_settings_field(
            'neuron_ai_default_provider_enhance',
            __('Default Provider for Enhancement', 'neuron-ai'),
            [new Fields($this->parent), 'default_provider_enhance_callback'],
            'neuron_ai_general',
            'neuron_ai_general_section'
        );

        add_settings_field(
            'neuron_ai_default_provider_search',
            __('Default Provider for Search', 'neuron-ai'),
            [new Fields($this->parent), 'default_provider_search_callback'],
            'neuron_ai_general',
            'neuron_ai_general_section'
        );

        add_settings_field(
            'neuron_ai_default_provider_chat',
            __('Default Provider for Chat', 'neuron-ai'),
            [new Fields($this->parent), 'default_provider_chat_callback'],
            'neuron_ai_general',
            'neuron_ai_general_section'
        );
        
        add_settings_field(
            'neuron_ai_enable_blocks',
            __('Enable Gutenberg Blocks', 'neuron-ai'),
            [new Fields($this->parent), 'enable_blocks_callback'],
            'neuron_ai_general',
            'neuron_ai_general_section'
        );
        
        // Content Settings section
        add_settings_section(
            'neuron_ai_content_section',
            __('Content Settings', 'neuron-ai'),
            [$this, 'content_section_callback'],
            'neuron_ai_content'
        );
        
        add_settings_field(
            'neuron_ai_default_tone',
            __('Default Tone', 'neuron-ai'),
            [new Fields($this->parent), 'default_tone_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_default_reading_level',
            __('Default Reading Level', 'neuron-ai'),
            [new Fields($this->parent), 'default_reading_level_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_preserve_tone',
            __('Preserve Document Tone', 'neuron-ai'),
            [new Fields($this->parent), 'preserve_tone_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_default_detail_level',
            __('Default Detail Level for Search', 'neuron-ai'),
            [new Fields($this->parent), 'default_detail_level_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_include_citations',
            __('Include Citations in Search Results', 'neuron-ai'),
            [new Fields($this->parent), 'include_citations_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_context_range',
            __('Context Range', 'neuron-ai'),
            [new Fields($this->parent), 'context_range_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );

        // Advanced Settings section
        add_settings_section(
            'neuron_ai_advanced_section',
            __('Advanced Settings', 'neuron-ai'),
            [$this, 'advanced_section_callback'],
            'neuron_ai_advanced'
        );

        add_settings_field(
            'neuron_ai_max_retries',
            __('Max Retries', 'neuron-ai'),
            [new Fields($this->parent), 'max_retries_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );

        add_settings_field(
            'neuron_ai_retry_delay',
            __('Retry Delay (ms)', 'neuron-ai'),
            [new Fields($this->parent), 'retry_delay_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );

        add_settings_field(
            'neuron_ai_log_retention',
            __('Log Retention (days)', 'neuron-ai'),
            [new Fields($this->parent), 'log_retention_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );

        add_settings_field(
            'neuron_ai_debug_mode',
            __('Debug Mode', 'neuron-ai'),
            [new Fields($this->parent), 'debug_mode_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );
        
        add_settings_field(
            'neuron_ai_cache_enabled',
            __('Enable Caching', 'neuron-ai'),
            [new Fields($this->parent), 'cache_enabled_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );
        
        add_settings_field(
            'neuron_ai_cache_ttl',
            __('Cache TTL (seconds)', 'neuron-ai'),
            [new Fields($this->parent), 'cache_ttl_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );
    }

    /**
     * Sanitize API key.
     *
     * @since    1.0.0
     * @param    string    $input    The input to sanitize.
     * @return   string              The sanitized input.
     */
    public function sanitize_api_key($input) {
        // Remove whitespace
        $input = trim($input);
        
        // Check if the input matches the expected format for API keys
        // Fixed the regex to properly allow hyphens and underscores
        if (!empty($input) && !preg_match('/^[a-zA-Z0-9_\-]+$/', $input)) {
            add_settings_error(
                'neuron_ai_api_keys',
                'invalid_api_key',
                __('The API key contains invalid characters.', 'neuron-ai')
            );
        }
        
        return $input;
    }

    /**
     * API Keys section callback.
     *
     * @since    1.0.0
     */
    public function api_keys_section_callback() {
        ?>
        <p><?php _e('Enter your API keys for the AI providers.', 'neuron-ai'); ?></p>
        <?php
    }

    /**
     * General section callback.
     *
     * @since    1.0.0
     */
    public function general_section_callback() {
        ?>
        <p><?php _e('Configure default providers for different operations.', 'neuron-ai'); ?></p>
        <?php
    }

    /**
     * Content section callback.
     *
     * @since    1.0.0
     */
    public function content_section_callback() {
        ?>
        <p><?php _e('Configure default content settings for AI operations.', 'neuron-ai'); ?></p>
        <?php
    }

    /**
     * Advanced section callback.
     *
     * @since    1.0.0
     */
    public function advanced_section_callback() {
        ?>
        <p><?php _e('Advanced settings for API interactions and debugging.', 'neuron-ai'); ?></p>
        <?php
    }
    
    /**
     * Get sections.
     *
     * @since    1.0.0
     * @return   array    The sections.
     */
    public function get_sections() {
        return $this->sections;
    }
}