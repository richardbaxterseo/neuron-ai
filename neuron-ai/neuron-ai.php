<?php
/**
 * Neuron AI
 *
 * @package    NeuronAI
 * @author     Your Name
 * @copyright  2023 Your Company
 * @license    GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Neuron AI
 * Plugin URI:        https://yourdomain.com/neuron-ai
 * Description:       AI-powered content enhancement for WordPress using Claude and Gemini AI models
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://yourdomain.com
 * Text Domain:       neuron-ai
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('NEURON_AI_VERSION', '1.0.0');
define('NEURON_AI_FILE', __FILE__);
define('NEURON_AI_PATH', plugin_dir_path(__FILE__));
define('NEURON_AI_URL', plugin_dir_url(__FILE__));
define('NEURON_AI_BASENAME', plugin_basename(__FILE__));

// Check for required PHP version
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        $message = sprintf(
            __('Neuron AI requires PHP version %s or higher. You are running version %s. Please upgrade your PHP version.', 'neuron-ai'),
            '7.4',
            PHP_VERSION
        );
        printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($message));
    });
    return;
}

// Ensure required directories exist
function neuron_ai_ensure_directories() {
    $directories = [
        NEURON_AI_PATH . 'includes/settings',
        NEURON_AI_PATH . 'includes/providers',
        NEURON_AI_PATH . 'includes/traits'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
}

neuron_ai_ensure_directories();

// In the neuron_ai_autoloader function in neuron-ai.php, modify the traits handling section:

// Special case for traits
$trait_parts = explode('\\', $class_name);
if (count($trait_parts) > 1 && $trait_parts[1] === 'Traits') {
    $trait_name = end($trait_parts);
    $trait_file = NEURON_AI_PATH . 'includes/traits/trait-' . strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $trait_name)) . '.php';
    if (file_exists($trait_file)) {
        require_once $trait_file;
        return;
    }
}

/**
 * Autoload classes.
 *
 * @param string $class_name The class name to load.
 */
function neuron_ai_autoloader($class_name) {
    // Only load classes in the NeuronAI namespace
    if (strpos($class_name, 'NeuronAI\\') !== 0) {
        return;
    }

    // Special case for traits
        $trait_parts = explode('\\', $class_name);
    if (count($trait_parts) > 1 && $trait_parts[1] === 'Traits') {
        $trait_name = end($trait_parts);
        $trait_file = NEURON_AI_PATH . 'includes/traits/trait-' . strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $trait_name)) . '.php';
    if (file_exists($trait_file)) {
        require_once $trait_file;
        return;
    }
}

    // For API class specifically
    if (strtolower($class_file) === 'api') {
        $file = NEURON_AI_PATH . 'includes/class-api.php';
    if (file_exists($file)) {
        require_once $file;
        return;
        }
    }

    // Remove namespace prefix
    $class_file = str_replace('NeuronAI\\', '', $class_name);
    
    // Convert namespace separators to directory separators
    $class_file = str_replace('\\', DIRECTORY_SEPARATOR, $class_file);
    
    // Convert class name to file name format (CamelCase to kebab-case)
    $class_file = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $class_file));
    
    // Build the complete path
    $file = NEURON_AI_PATH . 'includes' . DIRECTORY_SEPARATOR . 'class-' . $class_file . '.php';
    
    // Check for file in base includes directory
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Check for file in subdirectories
    $subdirs = ['providers', 'utils', 'blocks', 'traits', 'settings'];
    foreach ($subdirs as $subdir) {
        $namespace_part = strtolower(explode('\\', $class_name)[1] ?? '');
        
        // If namespace matches subdir, look there
        if ($namespace_part === $subdir) {
            $class_without_namespace = str_replace('NeuronAI\\' . ucfirst($subdir) . '\\', '', $class_name);
            $file_name = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $class_without_namespace));
            $file = NEURON_AI_PATH . 'includes/' . $subdir . '/class-' . $file_name . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
        
        // Try each subdirectory
        $file = NEURON_AI_PATH . 'includes/' . $subdir . '/class-' . $class_file . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Special case for interfaces
    $interface_file = NEURON_AI_PATH . 'includes/providers/interface-' . $class_file . '.php';
    if (file_exists($interface_file)) {
        require_once $interface_file;
        return;
    }
    
    // Special case for traits
    $trait_parts = explode('\\', $class_name);
    if (count($trait_parts) >= 2 && $trait_parts[1] === 'Traits') {
        $trait_name = end($trait_parts);
        $trait_file = NEURON_AI_PATH . 'includes/traits/trait-' . strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $trait_name)) . '.php';
        if (file_exists($trait_file)) {
            require_once $trait_file;
            return;
        }
    }
}

// Register the autoloader
spl_autoload_register('neuron_ai_autoloader');
// In neuron-ai.php before plugin initialization
require_once NEURON_AI_PATH . 'includes/class-api.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_neuron_ai');
register_deactivation_hook(__FILE__, 'deactivate_neuron_ai');

/**
 * The code that runs during plugin activation.
 */
function activate_neuron_ai() {
    // Ensure directories exist
    neuron_ai_ensure_directories();
    
    // Create activator and run activation
    $activator = new NeuronAI\Activator();
    $activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_neuron_ai() {
    $deactivator = new NeuronAI\Deactivator();
    $deactivator->deactivate();
}

/**
 * Begins execution of the plugin.
 */
function run_neuron_ai() {
    try {
        // Create plugin instance
        $plugin = new NeuronAI\Plugin();
        
        // Register custom block category
        add_filter('block_categories_all', function($categories) {
            $categories[] = [
                'slug'  => 'neuron-ai',
                'title' => __('Neuron AI', 'neuron-ai'),
                'icon'  => 'dashicons-welcome-learn-more',
            ];
            return $categories;
        });
        
        // Register blocks
        add_action('init', function() {
            if (function_exists('register_block_type')) {
                register_block_type(NEURON_AI_PATH . 'blocks/neuron-text');
                register_block_type(NEURON_AI_PATH . 'blocks/neuron-search');
            }
        });
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
            $settings_link = '<a href="' . admin_url('admin.php?page=neuron-ai-settings') . '">' . __('Settings', 'neuron-ai') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        });
        
    } catch (Exception $e) {
        // If there's an error during plugin initialization, register a fallback admin page
        add_action('admin_menu', 'neuron_ai_fallback_menu');
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p><strong>Neuron AI Plugin Error:</strong> <?php echo esc_html($e->getMessage()); ?></p>
                <p>Please check the <a href="<?php echo admin_url('admin.php?page=neuron-ai-error'); ?>">error details</a> page for more information.</p>
            </div>
            <?php
        });
    }
}

/**
 * Fallback admin menu in case of plugin initialization error
 */
function neuron_ai_fallback_menu() {
    add_menu_page(
        'Neuron AI Error',
        'Neuron AI',
        'manage_options',
        'neuron-ai-error',
        'neuron_ai_error_page',
        'dashicons-warning',
        80
    );
}

/**
 * Error page content
 */
function neuron_ai_error_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div style="background-color: #fff; padding: 20px; border-radius: 5px; border: 1px solid #ccc; margin-top: 20px;">
            <h2>Plugin Initialization Error</h2>
            <p>There was an error initializing the Neuron AI plugin. Below is diagnostic information that may help resolve the issue.</p>
            
            <h3>Plugin File Structure</h3>
            <div style="background-color: #f8f8f8; padding: 15px; border-radius: 3px;">
                <?php
                $plugin_dir = NEURON_AI_PATH;
                $includes_dir = $plugin_dir . 'includes/';
                
                echo '<h4>Plugin Root Directory:</h4>';
                echo '<pre>' . esc_html($plugin_dir) . '</pre>';
                
                echo '<h4>Key Files Status:</h4>';
                $key_files = [
                    'includes/class-api.php',
                    'includes/class-plugin.php',
                    'includes/class-container.php',
                    'includes/class-settings.php',
                    'includes/class-logger.php',
                    'includes/traits/trait-container-aware.php',
                    'includes/providers/class-provider-factory.php',
                    'includes/providers/interface-provider.php',
                    'includes/providers/abstract-provider.php',
                    'includes/providers/class-provider-constants.php',
                    'includes/providers/class-provider-error-handler.php',
                    'includes/settings/class-settings-api.php',
                    'includes/settings/class-settings-fields.php',
                    'includes/settings/class-settings-display.php',
                    'includes/settings/class-settings-ajax.php',
                ];
                
                echo '<ul>';
                foreach ($key_files as $file) {
                    $full_path = $plugin_dir . $file;
                    $exists = file_exists($full_path);
                    $status_color = $exists ? 'green' : 'red';
                    $status_text = $exists ? 'Found' : 'Missing';
                    echo '<li style="color: ' . $status_color . ';">' . esc_html($file) . ': ' . $status_text . '</li>';
                }
                echo '</ul>';
                
                if (file_exists($includes_dir)) {
                    echo '<h4>Includes Directory Content:</h4>';
                    $includes_files = scandir($includes_dir);
                    echo '<ul>';
                    foreach ($includes_files as $file) {
                        if ($file != '.' && $file != '..') {
                            echo '<li>' . esc_html($file) . '</li>';
                        }
                    }
                    echo '</ul>';
                    
                    // Check providers directory
                    $providers_dir = $includes_dir . 'providers/';
                    if (file_exists($providers_dir)) {
                        echo '<h4>Providers Directory Content:</h4>';
                        $providers_files = scandir($providers_dir);
                        echo '<ul>';
                        foreach ($providers_files as $file) {
                            if ($file != '.' && $file != '..') {
                                echo '<li>' . esc_html($file) . '</li>';
                            }
                        }
                        echo '</ul>';
                    }
                    
                    // Check settings directory
                    $settings_dir = $includes_dir . 'settings/';
                    if (file_exists($settings_dir)) {
                        echo '<h4>Settings Directory Content:</h4>';
                        $settings_files = scandir($settings_dir);
                        echo '<ul>';
                        foreach ($settings_files as $file) {
                            if ($file != '.' && $file != '..') {
                                echo '<li>' . esc_html($file) . '</li>';
                            }
                        }
                        echo '</ul>';
                    }
                }
                ?>
            </div>
            
            <h3>Class Checking</h3>
            <div style="background-color: #f8f8f8; padding: 15px; border-radius: 3px;">
                <?php
                $classes_to_check = [
                    'NeuronAI\\Plugin',
                    'NeuronAI\\Container',
                    'NeuronAI\\Settings',
                    'NeuronAI\\Logger',
                    'NeuronAI\\API',
                    'NeuronAI\\BlockManager',
                    'NeuronAI\\Providers\\ProviderFactory',
                    'NeuronAI\\Providers\\ProviderErrorHandler',
                    'NeuronAI\\Traits\\ContainerAware',
                    'NeuronAI\\Settings\\API',
                    'NeuronAI\\Settings\\Fields',
                    'NeuronAI\\Settings\\Display',
                    'NeuronAI\\Settings\\Ajax'
                ];
                
                echo '<ul>';
                foreach ($classes_to_check as $class) {
                    $exists = class_exists($class);
                    $status_color = $exists ? 'green' : 'red';
                    $status_text = $exists ? 'Available' : 'Not Available';
                    echo '<li style="color: ' . $status_color . ';">' . esc_html($class) . ': ' . $status_text . '</li>';
                }
                echo '</ul>';
                ?>
            </div>
            
            <h3>PHP Information</h3>
            <div style="background-color: #f8f8f8; padding: 15px; border-radius: 3px;">
                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
                <p><strong>Active Theme:</strong> <?php echo wp_get_theme()->get('Name'); ?></p>
            </div>
        </div>
    </div>
    <?php
}

// Start the plugin
run_neuron_ai();