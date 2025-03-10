<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * endpoint integrations.
 */
class Plugin {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Container    $container    Maintains and registers all hooks for the plugin.
     */
    protected $container;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->container = new Container();
        $this->load_dependencies();
        $this->register_services();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_api_hooks();
        
        // Register activation hook
        register_activation_hook(NEURON_AI_FILE, [$this, 'activate_plugin']);
        
        // Register deactivation hook
        register_deactivation_hook(NEURON_AI_FILE, [$this, 'deactivate_plugin']);
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Load Composer autoloader if it exists
        if (file_exists(NEURON_AI_PATH . 'vendor/autoload.php')) {
            require_once NEURON_AI_PATH . 'vendor/autoload.php';
        }
    }

    /**
     * Register services with the container.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_services() {
        // Register logger service
        $this->container->register('logger', function () {
            return new Logger();
        });

        // Register provider error handler
        $this->container->register('provider_error_handler', function ($container) {
            $handler = new Providers\ProviderErrorHandler();
            $handler->setContainer($container);
            return $handler;
        });

        // Register provider factory
        $this->container->register('provider_factory', function ($container) {
            $factory = new Providers\ProviderFactory();
            $factory->setContainer($container);
            return $factory;
        });

        // Register API service
        $this->container->register('api', function ($container) {
            $api = new API();
            $api->setContainer($container);
            return $api;
        });

        // Register settings service
        $this->container->register('settings', function ($container) {
            $settings = new Settings();
            $settings->setContainer($container);
            return $settings;
        });
        
        // Register block manager
        $this->container->register('block_manager', function ($container) {
            $block_manager = new BlockManager();
            $block_manager->setContainer($container);
            return $block_manager;
        });
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $i18n = new I18n();
        add_action('plugins_loaded', [$i18n, 'load_plugin_textdomain']);
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $settings = $this->container->get('settings');

        // Register admin menu and settings
        add_action('admin_menu', [$settings, 'register_menu']);
        add_action('admin_init', [$settings, 'register_settings']);

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$settings, 'enqueue_scripts']);
    }

    /**
     * Register all of the hooks related to the API functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_api_hooks() {
        $api = $this->container->get('api');

        // Register API endpoints
        add_action('rest_api_init', [$api, 'register_routes']);
    }
    
    /**
     * Define Block Manager hooks
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_block_hooks() {
        $block_manager = $this->container->get('block_manager');
        
        // Register blocks
        $block_manager->register_blocks();
    }

    /**
     * Plugin activation hook.
     *
     * @since    1.0.0
     */
    public function activate_plugin() {
        // Create logs database table
        $logger = $this->container->get('logger');
        $logger->create_logs_table();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook.
     *
     * @since    1.0.0
     */
    public function deactivate_plugin() {
        // Remove scheduled events
        wp_clear_scheduled_hook('neuron_ai_cleanup_logs');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Get the container instance.
     *
     * @since    1.0.0
     * @return   Container    The service container.
     */
    public function get_container() {
        return $this->container;
    }
}