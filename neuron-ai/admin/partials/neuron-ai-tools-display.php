<?php
/**
 * Provide a tools page for the plugin
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
    
    <div class="neuron-ai-header">
        <div class="neuron-ai-header-info">
            <p><?php _e('Tools and utilities for Neuron AI plugin.', 'neuron-ai'); ?></p>
        </div>
    </div>
    
    <div class="neuron-ai-tools-container">
        <!-- Cache Management Section -->
        <div class="neuron-ai-tool-section">
            <h2><?php _e('Cache Management', 'neuron-ai'); ?></h2>
            <p><?php _e('Manage the plugin\'s cache to optimize performance and storage.', 'neuron-ai'); ?></p>
            
            <div class="neuron-ai-tool-card">
                <h3><?php _e('Clear Cache', 'neuron-ai'); ?></h3>
                <p><?php _e('Clear the API response cache to ensure fresh data.', 'neuron-ai'); ?></p>
                <button type="button" class="button button-primary" id="neuron-ai-clear-cache">
                    <?php _e('Clear Cache', 'neuron-ai'); ?>
                </button>
                <div id="neuron-ai-cache-status" class="neuron-ai-status-message" style="display: none;"></div>
            </div>
        </div>
        
        <!-- Content Statistics Section -->
        <div class="neuron-ai-tool-section">
            <h2><?php _e('Content Statistics', 'neuron-ai'); ?></h2>
            <p><?php _e('View statistics about AI-enhanced content on your site.', 'neuron-ai'); ?></p>
            
            <div class="neuron-ai-tool-card">
                <h3><?php _e('Usage Statistics', 'neuron-ai'); ?></h3>
                <div class="neuron-ai-stats-grid">
                    <div class="neuron-ai-stat-box">
                        <span class="neuron-ai-stat-title"><?php _e('Enhancements', 'neuron-ai'); ?></span>
                        <span class="neuron-ai-stat-value">
                            <?php 
                            $enhancement_count = absint(get_option('neuron_ai_enhancement_count', 0));
                            echo number_format($enhancement_count);
                            ?>
                        </span>
                    </div>
                    <div class="neuron-ai-stat-box">
                        <span class="neuron-ai-stat-title"><?php _e('Searches', 'neuron-ai'); ?></span>
                        <span class="neuron-ai-stat-value">
                            <?php 
                            $search_count = absint(get_option('neuron_ai_search_count', 0));
                            echo number_format($search_count);
                            ?>
                        </span>
                    </div>
                    <div class="neuron-ai-stat-box">
                        <span class="neuron-ai-stat-title"><?php _e('Chat Messages', 'neuron-ai'); ?></span>
                        <span class="neuron-ai-stat-value">
                            <?php 
                            $chat_count = absint(get_option('neuron_ai_chat_count', 0));
                            echo number_format($chat_count);
                            ?>
                        </span>
                    </div>
                    <div class="neuron-ai-stat-box">
                        <span class="neuron-ai-stat-title"><?php _e('Blocks Used', 'neuron-ai'); ?></span>
                        <span class="neuron-ai-stat-value">
                            <?php 
                            $blocks_count = absint(get_option('neuron_ai_blocks_count', 0));
                            echo number_format($blocks_count);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Diagnostic Tools Section -->
        <div class="neuron-ai-tool-section">
            <h2><?php _e('Diagnostic Tools', 'neuron-ai'); ?></h2>
            <p><?php _e('Tools to help troubleshoot and diagnose issues.', 'neuron-ai'); ?></p>
            
            <div class="neuron-ai-tool-card">
                <h3><?php _e('System Information', 'neuron-ai'); ?></h3>
                <table class="widefat" cellspacing="0">
                    <tbody>
                        <tr>
                            <th><?php _e('WordPress Version', 'neuron-ai'); ?></th>
                            <td><?php echo get_bloginfo('version'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('PHP Version', 'neuron-ai'); ?></th>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Plugin Version', 'neuron-ai'); ?></th>
                            <td><?php echo NEURON_AI_VERSION; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Debug Mode', 'neuron-ai'); ?></th>
                            <td><?php echo get_option('neuron_ai_debug_mode', false) ? __('Enabled', 'neuron-ai') : __('Disabled', 'neuron-ai'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Caching', 'neuron-ai'); ?></th>
                            <td><?php echo get_option('neuron_ai_cache_enabled', true) ? __('Enabled', 'neuron-ai') : __('Disabled', 'neuron-ai'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Blocks Enabled', 'neuron-ai'); ?></th>
                            <td><?php echo get_option('neuron_ai_enable_blocks', true) ? __('Yes', 'neuron-ai') : __('No', 'neuron-ai'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>