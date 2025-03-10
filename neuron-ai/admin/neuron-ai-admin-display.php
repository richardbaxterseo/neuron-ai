<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.inspector.dev
 * @since      1.0.0
 *
 * @package    NeuronAI
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap neuron-ai-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="neuron-ai-header">
        <div class="neuron-ai-header-info">
            <p><?php _e('Enhance your WordPress content creation with AI capabilities powered by Anthropic Claude and Google Gemini.', 'neuron-ai'); ?></p>
        </div>
    </div>
    
    <?php 
    // Get current tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    ?>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=neuron-ai&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General Settings', 'neuron-ai'); ?>
        </a>
        <a href="?page=neuron-ai&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Advanced', 'neuron-ai'); ?>
        </a>
    </h2>
    
    <form method="post" action="options.php">
        <?php
        // Output settings fields
        settings_fields('neuron_ai_settings');
        
        // Output different settings sections based on active tab
        if ($active_tab === 'general') {
            // Display sections in the general tab
            ?>
            <div class="neuron-ai-settings-section">
                <?php do_settings_sections('neuron_ai_settings'); ?>
            </div>
            <?php
        } elseif ($active_tab === 'advanced') {
            // Display sections in the advanced tab
            ?>
            <div class="neuron-ai-settings-section">
                <?php do_settings_sections('neuron_ai_advanced_settings'); ?>
            </div>
            <?php
        }
        
        // Output submit button
        submit_button();
        ?>
    </form>
    
    <div class="neuron-ai-footer">
        <div class="neuron-ai-support">
            <h3><?php _e('Need Help?', 'neuron-ai'); ?></h3>
            <p><?php _e('For support or feature requests, please visit our documentation or contact us.', 'neuron-ai'); ?></p>
            <a href="https://github.com/inspector-apm/neuron-ai" target="_blank" class="button button-secondary">
                <?php _e('Documentation', 'neuron-ai'); ?>
            </a>
        </div>
    </div>
</div>