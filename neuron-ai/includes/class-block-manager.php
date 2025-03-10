<?php
/**
 * Block Manager class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

use NeuronAI\Traits\ContainerAware;

/**
 * Block Manager class.
 *
 * Handles registration and management of Gutenberg blocks.
 */
class BlockManager {

    use ContainerAware;

    /**
     * The blocks directory.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $blocks_dir    The blocks directory.
     */
    private $blocks_dir;

    /**
     * The blocks URL.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $blocks_url    The blocks URL.
     */
    private $blocks_url;

    /**
     * The registered blocks.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $blocks    The registered blocks.
     */
    private $blocks = [];

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->blocks_dir = NEURON_AI_PATH . 'blocks/';
        $this->blocks_url = NEURON_AI_URL . 'blocks/';
    }

    /**
     * Register the blocks.
     *
     * @since    1.0.0
     */
    public function register_blocks() {
        // Register block category
        add_filter('block_categories_all', [$this, 'register_block_category']);
        
        // Register blocks
        add_action('init', [$this, 'register_all_blocks']);
        
        // Enqueue block assets
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
    }

    /**
     * Register block category.
     *
     * @since    1.0.0
     * @param    array    $categories    Block categories.
     * @return   array                   Modified block categories.
     */
    public function register_block_category($categories) {
        return array_merge(
            $categories,
            [
                [
                    'slug' => 'neuron-ai',
                    'title' => __('Neuron AI', 'neuron-ai'),
                    'icon' => 'dashboard',
                ],
            ]
        );
    }

    /**
     * Register all blocks.
     *
     * @since    1.0.0
     */
    public function register_all_blocks() {
        // Register the blocks
        $this->register_block('neuron-text');
        $this->register_block('neuron-search');
        
        // Allow other blocks to be registered
        do_action('neuron_ai_register_blocks', $this);
    }

    /**
     * Register a block.
     *
     * @since    1.0.0
     * @param    string    $block_name    The block name.
     */
    public function register_block($block_name) {
        $block_dir = $this->blocks_dir . $block_name;
        $block_json_file = $block_dir . '/block.json';
        
        if (!file_exists($block_json_file)) {
            return;
        }
        
        // Register the block
        register_block_type($block_json_file);
        
        // Add to registered blocks
        $this->blocks[] = $block_name;
    }

    /**
     * Enqueue block editor assets.
     *
     * @since    1.0.0
     */
    public function enqueue_block_editor_assets() {
        // Enqueue shared assets
        wp_enqueue_script(
            'neuron-ai-blocks',
            NEURON_AI_URL . 'assets/js/blocks.js',
            ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-api-fetch'],
            NEURON_AI_VERSION,
            true
        );
        
        wp_enqueue_style(
            'neuron-ai-blocks',
            NEURON_AI_URL . 'assets/css/blocks.css',
            ['wp-edit-blocks'],
            NEURON_AI_VERSION
        );
        
        // Localize script with API data
        wp_localize_script('neuron-ai-blocks', 'neuronAI', [
            'apiUrl' => esc_url_raw(rest_url('neuron-ai/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'providers' => $this->get_providers_data(),
        ]);
    }

    /**
     * Get providers data for the editor.
     *
     * @since    1.0.0
     * @return   array    Providers data.
     */
    private function get_providers_data() {
        if (!$this->container) {
            return [];
        }
        
        try {
            $provider_factory = $this->getService('provider_factory');
            $status = $provider_factory->getProvidersStatus();
            
            $providers = [];
            foreach ($status as $provider_id => $provider_data) {
                if ($provider_data['has_api_key']) {
                    $providers[$provider_id] = [
                        'name' => $provider_data['name'],
                        'connected' => $provider_data['connected'],
                        'model' => $provider_data['model_name'],
                        'capabilities' => $provider_data['capabilities'] ?? []
                    ];
                }
            }
            
            return $providers;
        } catch (\Exception $e) {
            return [];
        }
    }
}