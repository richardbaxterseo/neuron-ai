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
        
        // Load sub-components
        $this->load_components();
    }

    /**
     * Load class components.
     *
     * @since    1.0.0
     */
    private function load_components() {
        require_once NEURON_AI_PATH . 'includes/settings/class-settings-api.php';
        require_once NEURON_AI_PATH . 'includes/settings/class-settings-fields.php';
        require_once NEURON_AI_PATH . 'includes/settings/class-settings-display.php';
        require_once NEURON_AI_PATH . 'includes/settings/class-settings-ajax.php';
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
        // Call the API registration method from the settings-api component
        $api = new Settings\API($this);
        $api->register_settings();
        
        // Set the sections from the API
        $this->sections = $api->get_sections();
        
        // Set active tab
        if (isset($_GET['tab'])) {
            $this->active_tab = sanitize_text_field($_GET['tab']);
        }
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
        
        // Use the display component
        $display = new Settings\Display($this);
        $display->render_settings_page($this->sections, $this->active_tab, $this->provider_factory);
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
        // Delegate to the AJAX component
        $ajax = new Settings\Ajax($this);
        $ajax->test_connection();
    }

    /**
     * AJAX handler for clearing the cache.
     *
     * @since    1.0.0
     */
    public function ajax_clear_cache() {
        // Delegate to the AJAX component
        $ajax = new Settings\Ajax($this);
        $ajax->clear_cache();
    }
    
    /**
     * Get active tab.
     *
     * @since    1.0.0
     * @return   string    The active tab.
     */
    public function get_active_tab() {
        return $this->active_tab;
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