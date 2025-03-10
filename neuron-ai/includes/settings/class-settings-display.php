<?php
/**
 * Settings display class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Settings
 */

namespace NeuronAI\Settings;

/**
 * Display class for settings UI.
 *
 * Handles rendering of settings UI components.
 */
class Display {

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
     * Render the settings page.
     *
     * @since    1.0.0
     * @param    array     $sections           The settings sections.
     * @param    string    $active_tab         The active tab.
     * @param    object    $provider_factory   The provider factory.
     */
    public function render_settings_page($sections, $active_tab, $provider_factory) {
        ?>
        <div class="wrap neuron-ai-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="neuron-ai-header">
                <div class="neuron-ai-header-info">
                    <p><?php _e('Configure Neuron AI settings and API keys.', 'neuron-ai'); ?></p>
                </div>
                
                <?php $this->display_provider_status($provider_factory); ?>
            </div>
            
            <h2 class="nav-tab-wrapper">
                <?php foreach ($sections as $tab_id => $tab_name) : ?>
                    <a href="?page=neuron-ai-settings&tab=<?php echo $tab_id; ?>" class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo $tab_name; ?>
                    </a>
                <?php endforeach; ?>
            </h2>
            
            <form method="post" action="options.php" id="neuron-ai-settings-form">
                <?php
                settings_fields('neuron_ai_' . $active_tab);
                do_settings_sections('neuron_ai_' . $active_tab);
                submit_button();
                ?>
            </form>
            
            <?php if ($active_tab === 'api_keys') : ?>
                <div id="neuron-ai-connection-test-results" class="neuron-ai-connection-test-results" style="display: none;">
                    <h3><?php _e('Connection Test Results', 'neuron-ai'); ?></h3>
                    <div class="neuron-ai-test-results-content"></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Display provider status.
     *
     * @since    1.0.0
     * @param    object    $provider_factory    The provider factory.
     */
    public function display_provider_status($provider_factory) {
        $status = [];
        
        if ($provider_factory) {
            $status = $provider_factory->getProvidersStatus();
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
}