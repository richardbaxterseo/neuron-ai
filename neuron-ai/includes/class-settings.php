<?php
/**
 * The settings class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

use NeuronAI\Traits\ContainerAware;
use NeuronAI\Providers\ProviderConstants;

/**
 * Settings class.
 *
 * Manages plugin settings and admin UI.
 */
class Settings {

    use ContainerAware;

    /**
     * The provider factory.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Providers\ProviderFactory    $provider_factory    The provider factory.
     */
    private $provider_factory;

    /**
     * The settings sections.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $sections    The settings sections.
     */
    private $sections = [];

    /**
     * The active tab.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $active_tab    The active tab.
     */
    private $active_tab = 'api_keys';

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Will be initialized when container is set
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
    }

    /**
     * Initialize services after container is set.
     *
     * @since    1.0.0
     */
    private function initServices() {
        if ($this->container && !$this->provider_factory) {
            $this->provider_factory = $this->getService('provider_factory');
        }
    }

    /**
     * Register the admin menu.
     *
     * @since    1.0.0
     */
    public function register_menu() {
        // Main menu item
        add_menu_page(
            __('Neuron AI', 'neuron-ai'),
            __('Neuron AI', 'neuron-ai'),
            'manage_options',
            'neuron-ai-settings',
            [$this, 'display_settings_page'],
            'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2ZM10 16C6.68629 16 4 13.3137 4 10C4 6.68629 6.68629 4 10 4C13.3137 4 16 6.68629 16 10C16 13.3137 13.3137 16 10 16ZM10 6C8.34315 6 7 7.34315 7 9V11C7 12.6569 8.34315 14 10 14C11.6569 14 13 12.6569 13 11V9C13 7.34315 11.6569 6 10 6Z" fill="currentColor"/></svg>'),
            '80'
        );

        // Settings submenu
        add_submenu_page(
            'neuron-ai-settings',
            __('Settings', 'neuron-ai'),
            __('Settings', 'neuron-ai'),
            'manage_options',
            'neuron-ai-settings',
            [$this, 'display_settings_page']
        );

        // Logs submenu
        add_submenu_page(
            'neuron-ai-settings',
            __('Logs', 'neuron-ai'),
            __('Logs', 'neuron-ai'),
            'manage_options',
            'neuron-ai-logs',
            [$this, 'display_logs_page']
        );
        
        // Tools submenu
        add_submenu_page(
            'neuron-ai-settings',
            __('Tools', 'neuron-ai'),
            __('Tools', 'neuron-ai'),
            'manage_options',
            'neuron-ai-tools',
            [$this, 'display_tools_page']
        );
        
        // About submenu
        add_submenu_page(
            'neuron-ai-settings',
            __('About', 'neuron-ai'),
            __('About', 'neuron-ai'),
            'manage_options',
            'neuron-ai-about',
            [$this, 'display_about_page']
        );
    }

    /**
     * Register settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Set the active tab
        if (isset($_GET['tab'])) {
            $this->active_tab = sanitize_text_field($_GET['tab']);
        }

        // Define sections
        $this->sections = [
            'api_keys' => __('API Keys', 'neuron-ai'),
            'general' => __('General', 'neuron-ai'),
            'content' => __('Content', 'neuron-ai'),
            'advanced' => __('Advanced', 'neuron-ai'),
        ];

        // Register settings
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
            [$this, 'anthropic_key_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );

        add_settings_field(
            'neuron_ai_claude_model',
            __('Claude Model', 'neuron-ai'),
            [$this, 'claude_model_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );

        add_settings_field(
            'neuron_ai_gemini_key',
            __('Gemini API Key', 'neuron-ai'),
            [$this, 'gemini_key_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );

        add_settings_field(
            'neuron_ai_gemini_model',
            __('Gemini Model', 'neuron-ai'),
            [$this, 'gemini_model_callback'],
            'neuron_ai_api_keys',
            'neuron_ai_api_keys_section'
        );
        
        add_settings_field(
            'neuron_ai_test_connection',
            __('Test Connections', 'neuron-ai'),
            [$this, 'test_connection_callback'],
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
            [$this, 'default_provider_enhance_callback'],
            'neuron_ai_general',
            'neuron_ai_general_section'
        );

        add_settings_field(
            'neuron_ai_default_provider_search',
            __('Default Provider for Search', 'neuron-ai'),
            [$this, 'default_provider_search_callback'],
            'neuron_ai_general',
            'neuron_ai_general_section'
        );

        add_settings_field(
            'neuron_ai_default_provider_chat',
            __('Default Provider for Chat', 'neuron-ai'),
            [$this, 'default_provider_chat_callback'],
            'neuron_ai_general',
            'neuron_ai_general_section'
        );
        
        add_settings_field(
            'neuron_ai_enable_blocks',
            __('Enable Gutenberg Blocks', 'neuron-ai'),
            [$this, 'enable_blocks_callback'],
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
            [$this, 'default_tone_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_default_reading_level',
            __('Default Reading Level', 'neuron-ai'),
            [$this, 'default_reading_level_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_preserve_tone',
            __('Preserve Document Tone', 'neuron-ai'),
            [$this, 'preserve_tone_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_default_detail_level',
            __('Default Detail Level for Search', 'neuron-ai'),
            [$this, 'default_detail_level_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_include_citations',
            __('Include Citations in Search Results', 'neuron-ai'),
            [$this, 'include_citations_callback'],
            'neuron_ai_content',
            'neuron_ai_content_section'
        );
        
        add_settings_field(
            'neuron_ai_context_range',
            __('Context Range', 'neuron-ai'),
            [$this, 'context_range_callback'],
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
            [$this, 'max_retries_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );

        add_settings_field(
            'neuron_ai_retry_delay',
            __('Retry Delay (ms)', 'neuron-ai'),
            [$this, 'retry_delay_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );

        add_settings_field(
            'neuron_ai_log_retention',
            __('Log Retention (days)', 'neuron-ai'),
            [$this, 'log_retention_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );

        add_settings_field(
            'neuron_ai_debug_mode',
            __('Debug Mode', 'neuron-ai'),
            [$this, 'debug_mode_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );
        
        add_settings_field(
            'neuron_ai_cache_enabled',
            __('Enable Caching', 'neuron-ai'),
            [$this, 'cache_enabled_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );
        
        add_settings_field(
            'neuron_ai_cache_ttl',
            __('Cache TTL (seconds)', 'neuron-ai'),
            [$this, 'cache_ttl_callback'],
            'neuron_ai_advanced',
            'neuron_ai_advanced_section'
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since    1.0.0
     * @param    string    $hook_suffix    The current admin page.
     */
    public function enqueue_scripts($hook_suffix) {
        if (!in_array($hook_suffix, [
            'toplevel_page_neuron-ai-settings', 
            'neuron-ai_page_neuron-ai-logs',
            'neuron-ai_page_neuron-ai-tools',
            'neuron-ai_page_neuron-ai-about'
        ])) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'neuron-ai-admin',
            NEURON_AI_URL . 'admin/css/neuron-ai-admin.css',
            [],
            NEURON_AI_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'neuron-ai-admin',
            NEURON_AI_URL . 'admin/js/neuron-ai-admin.js',
            ['jquery'],
            NEURON_AI_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('neuron-ai-admin', 'neuronAI', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => esc_url_raw(rest_url('neuron-ai/v1')),
            'nonce' => wp_create_nonce('neuron_ai_admin'),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);

        // If on logs page, enqueue logs scripts
        if ($hook_suffix === 'neuron-ai_page_neuron-ai-logs') {
            wp_enqueue_script(
                'neuron-ai-logs',
                NEURON_AI_URL . 'admin/js/neuron-ai-logs.js',
                ['jquery'],
                NEURON_AI_VERSION,
                true
            );

            wp_localize_script('neuron-ai-logs', 'neuronAI', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('neuron_ai_logs'),
            ]);
        }
        
        // If on tools page, enqueue tools scripts
        if ($hook_suffix === 'neuron-ai_page_neuron-ai-tools') {
            wp_enqueue_script(
                'neuron-ai-tools',
                NEURON_AI_URL . 'admin/js/neuron-ai-tools.js',
                ['jquery'],
                NEURON_AI_VERSION,
                true
            );

            wp_localize_script('neuron-ai-tools', 'neuronAI', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('neuron_ai_tools'),
            ]);
        }
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        $this->initServices();

        ?>
        <div class="wrap neuron-ai-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="neuron-ai-header">
                <div class="neuron-ai-header-info">
                    <p><?php _e('Configure Neuron AI settings and API keys.', 'neuron-ai'); ?></p>
                </div>
                
                <?php $this->display_provider_status(); ?>
            </div>
            
            <h2 class="nav-tab-wrapper">
                <?php foreach ($this->sections as $tab_id => $tab_name) : ?>
                    <a href="?page=neuron-ai-settings&tab=<?php echo $tab_id; ?>" class="nav-tab <?php echo $this->active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo $tab_name; ?>
                    </a>
                <?php endforeach; ?>
            </h2>
            
            <form method="post" action="options.php" id="neuron-ai-settings-form">
                <?php
                settings_fields('neuron_ai_' . $this->active_tab);
                do_settings_sections('neuron_ai_' . $this->active_tab);
                submit_button();
                ?>
            </form>
            
            <?php if ($this->active_tab === 'api_keys') : ?>
                <div id="neuron-ai-connection-test-results" class="neuron-ai-connection-test-results" style="display: none;">
                    <h3><?php _e('Connection Test Results', 'neuron-ai'); ?></h3>
                    <div class="neuron-ai-test-results-content"></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Display the logs page.
     *
     * @since    1.0.0
     */
    public function display_logs_page() {
        // Include the logs page template
        include NEURON_AI_PATH . 'admin/partials/neuron-ai-logs-display.php';
    }
    
    /**
     * Display the tools page.
     *
     * @since    1.0.0
     */
    public function display_tools_page() {
        // Include the tools page template
        include NEURON_AI_PATH . 'admin/partials/neuron-ai-tools-display.php';
    }
    
    /**
     * Display the about page.
     *
     * @since    1.0.0
     */
    public function display_about_page() {
        // Include the about page template
        include NEURON_AI_PATH . 'admin/partials/neuron-ai-about-display.php';
    }

    /**
     * Display provider status.
     *
     * @since    1.0.0
     */
    private function display_provider_status() {
        $status = [];
        
        if ($this->provider_factory) {
            $status = $this->provider_factory->getProvidersStatus();
        }
        
        if (empty($status)) {
            return;
        }
        
        ?>
        <div class="neuron-ai-provider-status">
            <?php foreach ($status as $provider_id => $provider_data) : ?>
                <div class="neuron-ai-provider-card">
                    <div class="neuron-ai-provider-card-header">
                        <h3><?php echo $provider_data['name']; ?></h3>
                        <span class="neuron-ai-provider-status-indicator <?php echo $provider_data['connected'] ? 'connected' : 'disconnected'; ?>">
                            <?php echo $provider_data['connected'] ? __('Connected', 'neuron-ai') : __('Disconnected', 'neuron-ai'); ?>
                        </span>
                    </div>
                    
                    <div class="neuron-ai-provider-card-body">
                        <?php if ($provider_data['has_api_key']) : ?>
                            <p>
                                <strong><?php _e('Model:', 'neuron-ai'); ?></strong>
                                <?php echo $provider_data['model_name']; ?>
                            </p>
                            
                            <?php if (!empty($provider_data['capabilities'])) : ?>
                                <p>
                                    <strong><?php _e('Capabilities:', 'neuron-ai'); ?></strong>
                                    <?php 
                                    $capability_names = [
                                        'enhance' => __('Content Enhancement', 'neuron-ai'),
                                        'search' => __('Information Search', 'neuron-ai'),
                                        'chat' => __('Conversations', 'neuron-ai'),
                                        'vision' => __('Vision', 'neuron-ai'),
                                        'embed' => __('Embeddings', 'neuron-ai'),
                                    ];
                                    
                                    $active_capabilities = [];
                                    foreach ($provider_data['capabilities'] as $capability => $supported) {
                                        if ($supported && isset($capability_names[$capability])) {
                                            $active_capabilities[] = $capability_names[$capability];
                                        }
                                    }
                                    
                                    echo implode(', ', $active_capabilities);
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!$provider_data['connected']) : ?>
                                <p class="neuron-ai-provider-error">
                                    <?php echo isset($provider_data['message']) ? esc_html($provider_data['message']) : __('Unable to connect to provider.', 'neuron-ai'); ?>
                                </p>
                            <?php endif; ?>
                        <?php else : ?>
                            <p><?php printf(__('API key not configured. <a href="%s">Add your API key</a>.', 'neuron-ai'), admin_url('admin.php?page=neuron-ai-settings&tab=api_keys')); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
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
     * Anthropic key callback.
     *
     * @since    1.0.0
     */
    public function anthropic_key_callback() {
        $api_key = get_option('neuron_ai_anthropic_key', '');
        $masked_key = $api_key ? substr($api_key, 0, 4) . str_repeat('•', 10) . substr($api_key, -4) : '';
        $show_key = isset($_GET['show_keys']) && $_GET['show_keys'] === '1';
        
        ?>
        <input type="<?php echo $show_key ? 'text' : 'password'; ?>" 
               id="neuron_ai_anthropic_key" 
               name="neuron_ai_anthropic_key" 
               class="regular-text" 
               value="<?php echo esc_attr($api_key); ?>" />
        
        <button type="button" 
                class="button neuron-ai-toggle-key" 
                data-target="neuron_ai_anthropic_key"
                data-show="<?php echo $show_key ? '1' : '0'; ?>">
            <?php echo $show_key ? __('Hide', 'neuron-ai') : __('Show', 'neuron-ai'); ?>
        </button>
        
        <p class="description">
            <?php _e('Enter your Claude API key. <a href="https://console.anthropic.com/keys" target="_blank">Get a Claude API key</a>.', 'neuron-ai'); ?>
        </p>
        <?php
    }

/**
 * Gemini key callback.
 *
 * @since    1.0.0
 */
public function gemini_key_callback() {
    $api_key = get_option('neuron_ai_gemini_key', '');
    $masked_key = $api_key ? substr($api_key, 0, 4) . str_repeat('•', 10) . substr($api_key, -4) : '';
    $show_key = isset($_GET['show_keys']) && $_GET['show_keys'] === '1';
    
    ?>
    <input type="<?php echo $show_key ? 'text' : 'password'; ?>" 
           id="neuron_ai_gemini_key" 
           name="neuron_ai_gemini_key" 
           class="regular-text" 
           value="<?php echo esc_attr($api_key); ?>" />
    
    <button type="button" 
            class="button neuron-ai-toggle-key" 
            data-target="neuron_ai_gemini_key"
            data-show="<?php echo $show_key ? '1' : '0'; ?>">
        <?php echo $show_key ? __('Hide', 'neuron-ai') : __('Show', 'neuron-ai'); ?>
    </button>
    
    <p class="description">
        <?php _e('Enter your Gemini API key. <a href="https://makersuite.google.com/app/apikey" target="_blank">Get a Gemini API key</a>.', 'neuron-ai'); ?>
    </p>
    <?php
}
/**
 * Gemini model callback.
 *
 * @since    1.0.0
 */
public function gemini_model_callback() {
    $current_model = get_option('neuron_ai_gemini_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_GEMINI));
    $gemini_models = ProviderConstants::getGeminiModels();
    
    ?>
    <select id="neuron_ai_gemini_model" name="neuron_ai_gemini_model">
        <?php foreach ($gemini_models as $model_id => $model_data) : ?>
            <option value="<?php echo esc_attr($model_id); ?>" <?php selected($current_model, $model_id); ?>>
                <?php echo $model_data['name']; ?> - <?php echo $model_data['description']; ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <p class="description">
        <?php _e('Select which Gemini model to use.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Test connection callback.
 * 
 * @since    1.0.0
 */
public function test_connection_callback() {
    ?>
    <button type="button" id="neuron-ai-test-claude" class="button">
        <?php _e('Test Claude Connection', 'neuron-ai'); ?>
    </button>
    
    <button type="button" id="neuron-ai-test-gemini" class="button">
        <?php _e('Test Gemini Connection', 'neuron-ai'); ?>
    </button>
    
    <p class="description">
        <?php _e('Test your API connections. Make sure to save your API keys first.', 'neuron-ai'); ?>
    </p>
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
 * Default provider for enhancement callback.
 *
 * @since    1.0.0
 */
public function default_provider_enhance_callback() {
    $current_provider = get_option('neuron_ai_default_provider_enhance', '');
    
    ?>
    <select id="neuron_ai_default_provider_enhance" name="neuron_ai_default_provider_enhance">
        <option value="" <?php selected($current_provider, ''); ?>><?php _e('Auto-select', 'neuron-ai'); ?></option>
        <option value="claude" <?php selected($current_provider, 'claude'); ?>><?php _e('Claude by Anthropic', 'neuron-ai'); ?></option>
        <option value="gemini" <?php selected($current_provider, 'gemini'); ?>><?php _e('Gemini by Google', 'neuron-ai'); ?></option>
    </select>
    <p class="description">
        <?php _e('Default is Claude for enhancement operations.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Default provider for search callback.
 *
 * @since    1.0.0
 */
public function default_provider_search_callback() {
    $current_provider = get_option('neuron_ai_default_provider_search', '');
    
    ?>
    <select id="neuron_ai_default_provider_search" name="neuron_ai_default_provider_search">
        <option value="" <?php selected($current_provider, ''); ?>><?php _e('Auto-select', 'neuron-ai'); ?></option>
        <option value="claude" <?php selected($current_provider, 'claude'); ?>><?php _e('Claude by Anthropic', 'neuron-ai'); ?></option>
        <option value="gemini" <?php selected($current_provider, 'gemini'); ?>><?php _e('Gemini by Google', 'neuron-ai'); ?></option>
    </select>
    <p class="description">
        <?php _e('Default is Gemini for search operations.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Default provider for chat callback.
 *
 * @since    1.0.0
 */
public function default_provider_chat_callback() {
    $current_provider = get_option('neuron_ai_default_provider_chat', '');
    
    ?>
    <select id="neuron_ai_default_provider_chat" name="neuron_ai_default_provider_chat">
        <option value="" <?php selected($current_provider, ''); ?>><?php _e('Auto-select', 'neuron-ai'); ?></option>
        <option value="claude" <?php selected($current_provider, 'claude'); ?>><?php _e('Claude by Anthropic', 'neuron-ai'); ?></option>
        <option value="gemini" <?php selected($current_provider, 'gemini'); ?>><?php _e('Gemini by Google', 'neuron-ai'); ?></option>
    </select>
    <p class="description">
        <?php _e('Default is Claude for chat operations.', 'neuron-ai'); ?>
    </p>
    <?php
}
/**
 * Enable blocks callback.
 *
 * @since    1.0.0
 */
public function enable_blocks_callback() {
    $enable_blocks = get_option('neuron_ai_enable_blocks', true);
    
    ?>
    <label>
        <input type="checkbox" id="neuron_ai_enable_blocks" name="neuron_ai_enable_blocks" value="1" <?php checked($enable_blocks); ?> />
        <?php _e('Enable Neuron AI Gutenberg blocks', 'neuron-ai'); ?>
    </label>
    <p class="description">
        <?php _e('If disabled, no Neuron AI blocks will be available in the editor.', 'neuron-ai'); ?>
    </p>
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
 * Default tone callback.
 *
 * @since    1.0.0
 */
public function default_tone_callback() {
    $current_tone = get_option('neuron_ai_default_tone', 'professional');
    
    ?>
    <select id="neuron_ai_default_tone" name="neuron_ai_default_tone">
        <option value="professional" <?php selected($current_tone, 'professional'); ?>><?php _e('Professional', 'neuron-ai'); ?></option>
        <option value="casual" <?php selected($current_tone, 'casual'); ?>><?php _e('Casual', 'neuron-ai'); ?></option>
        <option value="academic" <?php selected($current_tone, 'academic'); ?>><?php _e('Academic', 'neuron-ai'); ?></option>
        <option value="creative" <?php selected($current_tone, 'creative'); ?>><?php _e('Creative', 'neuron-ai'); ?></option>
    </select>
    <p class="description">
        <?php _e('Default tone for content enhancements.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Default reading level callback.
 *
 * @since    1.0.0
 */
public function default_reading_level_callback() {
    $current_level = get_option('neuron_ai_default_reading_level', 'universal');
    
    ?>
    <select id="neuron_ai_default_reading_level" name="neuron_ai_default_reading_level">
        <option value="universal" <?php selected($current_level, 'universal'); ?>><?php _e('Universal', 'neuron-ai'); ?></option>
        <option value="simple" <?php selected($current_level, 'simple'); ?>><?php _e('Simple', 'neuron-ai'); ?></option>
        <option value="intermediate" <?php selected($current_level, 'intermediate'); ?>><?php _e('Intermediate', 'neuron-ai'); ?></option>
        <option value="advanced" <?php selected($current_level, 'advanced'); ?>><?php _e('Advanced', 'neuron-ai'); ?></option>
    </select>
    <p class="description">
        <?php _e('Default reading level for content enhancements.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Preserve tone callback.
 *
 * @since    1.0.0
 */
public function preserve_tone_callback() {
    $preserve_tone = get_option('neuron_ai_preserve_tone', true);
    
    ?>
    <label>
        <input type="checkbox" id="neuron_ai_preserve_tone" name="neuron_ai_preserve_tone" value="1" <?php checked($preserve_tone); ?> />
        <?php _e('Analyze and preserve document tone', 'neuron-ai'); ?>
    </label>
    <p class="description">
        <?php _e('When enabled, AI will analyze your content and try to preserve its tone and style.', 'neuron-ai'); ?>
    </p>
    <?php
}
/**
 * Default detail level callback.
 *
 * @since    1.0.0
 */
public function default_detail_level_callback() {
    $current_level = get_option('neuron_ai_default_detail_level', 'medium');
    
    ?>
    <select id="neuron_ai_default_detail_level" name="neuron_ai_default_detail_level">
        <option value="brief" <?php selected($current_level, 'brief'); ?>><?php _e('Brief', 'neuron-ai'); ?></option>
        <option value="medium" <?php selected($current_level, 'medium'); ?>><?php _e('Medium', 'neuron-ai'); ?></option>
        <option value="comprehensive" <?php selected($current_level, 'comprehensive'); ?>><?php _e('Comprehensive', 'neuron-ai'); ?></option>
    </select>
    <p class="description">
        <?php _e('Default detail level for search results.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Include citations callback.
 *
 * @since    1.0.0
 */
public function include_citations_callback() {
    $include_citations = get_option('neuron_ai_include_citations', true);
    
    ?>
    <label>
        <input type="checkbox" id="neuron_ai_include_citations" name="neuron_ai_include_citations" value="1" <?php checked($include_citations); ?> />
        <?php _e('Include citations in search results', 'neuron-ai'); ?>
    </label>
    <p class="description">
        <?php _e('When enabled, search results will include citation suggestions.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Context range callback.
 *
 * @since    1.0.0
 */
public function context_range_callback() {
    $context_range = get_option('neuron_ai_context_range', 3);
    
    ?>
    <input type="number" id="neuron_ai_context_range" name="neuron_ai_context_range" value="<?php echo esc_attr($context_range); ?>" min="0" max="10" step="1" />
    <p class="description">
        <?php _e('Number of blocks to include before and after the current block for context. Higher values provide more context but may slow down performance.', 'neuron-ai'); ?>
    </p>
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
 * Max retries callback.
 *
 * @since    1.0.0
 */
public function max_retries_callback() {
    $max_retries = get_option('neuron_ai_max_retries', 3);
    
    ?>
    <input type="number" id="neuron_ai_max_retries" name="neuron_ai_max_retries" value="<?php echo esc_attr($max_retries); ?>" min="0" max="10" step="1" />
    <p class="description">
        <?php _e('Number of times to retry failed API requests.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Retry delay callback.
 *
 * @since    1.0.0
 */
public function retry_delay_callback() {
    $retry_delay = get_option('neuron_ai_retry_delay', 1000);
    
    ?>
    <input type="number" id="neuron_ai_retry_delay" name="neuron_ai_retry_delay" value="<?php echo esc_attr($retry_delay); ?>" min="500" max="10000" step="100" />
    <p class="description">
        <?php _e('Delay between retry attempts in milliseconds.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Log retention callback.
 *
 * @since    1.0.0
 */
public function log_retention_callback() {
    $log_retention = get_option('neuron_ai_log_retention', 30);
    
    ?>
    <input type="number" id="neuron_ai_log_retention" name="neuron_ai_log_retention" value="<?php echo esc_attr($log_retention); ?>" min="1" max="90" step="1" />
    <p class="description">
        <?php _e('Number of days to keep logs before automatic deletion.', 'neuron-ai'); ?>
    </p>
    <?php
}
/**
 * Debug mode callback.
 *
 * @since    1.0.0
 */
public function debug_mode_callback() {
    $debug_mode = get_option('neuron_ai_debug_mode', false);
    
    ?>
    <label>
        <input type="checkbox" id="neuron_ai_debug_mode" name="neuron_ai_debug_mode" value="1" <?php checked($debug_mode); ?> />
        <?php _e('Enable debug mode', 'neuron-ai'); ?>
    </label>
    <p class="description">
        <?php _e('Enables detailed logging for troubleshooting. This will significantly increase the number of logs generated.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Cache enabled callback.
 *
 * @since    1.0.0
 */
public function cache_enabled_callback() {
    $cache_enabled = get_option('neuron_ai_cache_enabled', true);
    
    ?>
    <label>
        <input type="checkbox" id="neuron_ai_cache_enabled" name="neuron_ai_cache_enabled" value="1" <?php checked($cache_enabled); ?> />
        <?php _e('Enable caching of API responses', 'neuron-ai'); ?>
    </label>
    <p class="description">
        <?php _e('When enabled, identical API requests will be cached to improve performance and reduce API costs.', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Cache TTL callback.
 *
 * @since    1.0.0
 */
public function cache_ttl_callback() {
    $cache_ttl = get_option('neuron_ai_cache_ttl', 3600);
    
    ?>
    <input type="number" id="neuron_ai_cache_ttl" name="neuron_ai_cache_ttl" value="<?php echo esc_attr($cache_ttl); ?>" min="60" max="86400" step="60" />
    <p class="description">
        <?php _e('How long to keep cached responses in seconds. Default is 3600 (1 hour).', 'neuron-ai'); ?>
    </p>
    <?php
}

/**
 * Register AJAX handlers for the settings page.
 *
 * @since    1.0.0
 */
public function register_ajax_handlers() {
    add_action('wp_ajax_neuron_ai_test_connection', [$this, 'ajax_test_connection']);
    add_action('wp_ajax_neuron_ai_clear_cache', [$this, 'ajax_clear_cache']);
}

/**
 * AJAX handler for testing API connections.
 *
 * @since    1.0.0
 */
public function ajax_test_connection() {
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
    
    $this->initServices();
    
    if (!$this->provider_factory) {
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
        
        $provider_instance = $this->provider_factory->getProvider($provider, $api_key, $model);
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
    public function ajax_clear_cache() {
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
