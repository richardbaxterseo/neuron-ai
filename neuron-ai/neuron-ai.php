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

/**
 * The code that runs during plugin activation.
 */
function activate_neuron_ai() {
    require_once NEURON_AI_PATH . 'includes/class-activator.php';
    $activator = new NeuronAI\Activator();
    $activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_neuron_ai() {
    require_once NEURON_AI_PATH . 'includes/class-deactivator.php';
    $deactivator = new NeuronAI\Deactivator();
    $deactivator->deactivate();
}

register_activation_hook(__FILE__, 'activate_neuron_ai');
register_deactivation_hook(__FILE__, 'deactivate_neuron_ai');

/**
 * The core plugin class.
 */
require_once NEURON_AI_PATH . 'includes/class-plugin.php';

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
    $subdirs = ['providers', 'utils', 'blocks', 'traits'];
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

// Register the autoloader
spl_autoload_register('neuron_ai_autoloader');

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_neuron_ai() {
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
    
    // Load blocks
    add_action('init', function() {
        // Register block types from directory
        if (function_exists('register_block_type')) {
            // Neuron Text block
            register_block_type(NEURON_AI_PATH . 'blocks/neuron-text');
            
            // Neuron Search block
            register_block_type(NEURON_AI_PATH . 'blocks/neuron-search');
        }
    });
    
    // Add settings link to plugins page
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=neuron-ai-settings') . '">' . __('Settings', 'neuron-ai') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    });
}

// Start the plugin
run_neuron_ai();
}