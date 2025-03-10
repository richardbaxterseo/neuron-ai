<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

    /**
     * Activate the plugin.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create database tables if needed
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create necessary directories
        self::create_directories();
        
        // Set activation flag
        update_option('neuron_ai_activated', true);
        update_option('neuron_ai_version', NEURON_AI_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables required by the plugin.
     *
     * @since    1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create logs table
        $table_name = $wpdb->prefix . 'neuron_ai_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            request_type varchar(50) NOT NULL,
            provider varchar(50) NOT NULL,
            status_code int(11) DEFAULT NULL,
            details text DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options for the plugin.
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        // Set default options if they don't exist
        if (!get_option('neuron_ai_settings')) {
            $default_settings = [
                'anthropic_api_key' => '',
                'gemini_api_key' => '',
                'default_provider' => 'claude',
                'debug_mode' => false,
                'log_retention_days' => 30,
            ];
            
            update_option('neuron_ai_settings', $default_settings);
        }
    }
    
    /**
     * Create necessary directories for the plugin.
     *
     * @since    1.0.0
     */
    private static function create_directories() {
        // Create uploads directory if needed
        $upload_dir = wp_upload_dir();
        $neuron_dir = $upload_dir['basedir'] . '/neuron-ai';
        
        if (!file_exists($neuron_dir)) {
            wp_mkdir_p($neuron_dir);
        }
        
        // Create .htaccess file to protect the directory
        if (!file_exists($neuron_dir . '/.htaccess')) {
            $htaccess_content = "# Deny access to all files\n";
            $htaccess_content .= "<Files \"*\">\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($neuron_dir . '/.htaccess', $htaccess_content);
        }
    }
}