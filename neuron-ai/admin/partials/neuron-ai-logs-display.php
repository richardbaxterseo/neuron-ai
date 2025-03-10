<?php
/**
 * Provide a logs view for the plugin
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
            <p><?php _e('View logs of AI API interactions for troubleshooting and analysis.', 'neuron-ai'); ?></p>
        </div>
    </div>
    
    <div class="neuron-ai-logs-container">
        <div class="neuron-ai-log-filters">
            <div class="form-field">
                <label for="neuron-ai-filter-provider"><?php _e('Provider:', 'neuron-ai'); ?></label>
                <select id="neuron-ai-filter-provider">
                    <option value=""><?php _e('All Providers', 'neuron-ai'); ?></option>
                    <option value="claude"><?php _e('Claude', 'neuron-ai'); ?></option>
                    <option value="gemini"><?php _e('Gemini', 'neuron-ai'); ?></option>
                </select>
            </div>
            
            <div class="form-field">
                <label for="neuron-ai-filter-type"><?php _e('Request Type:', 'neuron-ai'); ?></label>
                <select id="neuron-ai-filter-type">
                    <option value=""><?php _e('All Types', 'neuron-ai'); ?></option>
                    <option value="enhance"><?php _e('Enhance', 'neuron-ai'); ?></option>
                    <option value="search"><?php _e('Search', 'neuron-ai'); ?></option>
                    <option value="chat"><?php _e('Chat', 'neuron-ai'); ?></option>
                </select>
            </div>
            
            <div class="form-field">
                <label for="neuron-ai-filter-status"><?php _e('Status:', 'neuron-ai'); ?></label>
                <select id="neuron-ai-filter-status">
                    <option value=""><?php _e('All Statuses', 'neuron-ai'); ?></option>
                    <option value="success"><?php _e('Success', 'neuron-ai'); ?></option>
                    <option value="error"><?php _e('Error', 'neuron-ai'); ?></option>
                </select>
            </div>
            
            <div class="form-field">
                <label for="neuron-ai-filter-date"><?php _e('Date Range:', 'neuron-ai'); ?></label>
                <select id="neuron-ai-filter-date">
                    <option value="24h"><?php _e('Last 24 Hours', 'neuron-ai'); ?></option>
                    <option value="7d"><?php _e('Last 7 Days', 'neuron-ai'); ?></option>
                    <option value="30d"><?php _e('Last 30 Days', 'neuron-ai'); ?></option>
                    <option value="all"><?php _e('All Time', 'neuron-ai'); ?></option>
                </select>
            </div>
            
            <button type="button" class="button neuron-ai-filter-button" id="neuron-ai-filter-logs">
                <?php _e('Filter', 'neuron-ai'); ?>
            </button>
            
            <button type="button" class="button neuron-ai-clear-button" id="neuron-ai-clear-filters">
                <?php _e('Clear Filters', 'neuron-ai'); ?>
            </button>
        </div>
        
        <div class="neuron-ai-log-table-container">
            <!-- This will be populated by AJAX -->
            <p class="neuron-ai-log-loading"><?php _e('Loading logs...', 'neuron-ai'); ?></p>
            
            <div class="neuron-ai-log-actions">
                <button type="button" class="button neuron-ai-clear-logs-button" id="neuron-ai-clear-logs">
                    <?php _e('Clear Logs', 'neuron-ai'); ?>
                </button>
            </div>
        </div>
    </div>
</div>