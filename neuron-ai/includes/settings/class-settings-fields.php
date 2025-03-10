<?php
/**
 * Settings field callbacks class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Settings
 */

namespace NeuronAI\Settings;

use NeuronAI\Providers\ProviderConstants;

/**
 * Fields class for settings callbacks.
 *
 * Handles rendering of settings fields.
 */
class Fields {

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
     * Anthropic key callback.
     *
     * @since    1.0.0
     */
    public function anthropic_key_callback() {
        $api_key = get_option('neuron_ai_anthropic_key', '');
        $show_key = isset($_GET['show_keys']) && $_GET['show_keys'] === '1';
        
        ?>
        <input type="<?php echo $show_key ? 'text' : 'password'; ?>" 
               id="neuron_ai_anthropic_key" 
               name="neuron_ai_anthropic_key" 
               class="regular-text" 
               value="<?php echo esc_attr($api_key); ?>" />
        
        <button type="button" 
                class="button neuron-ai-toggle-key" 
                data-target="neuron_ai_anthropic_key"
                data-show="<?php echo $show_key ? '1' : '0'; ?>">
            <?php echo $show_key ? __('Hide', 'neuron-ai') : __('Show', 'neuron-ai'); ?>
        </button>
        
        <p class="description">
            <?php _e('Enter your Claude API key. <a href="https://console.anthropic.com/keys" target="_blank">Get a Claude API key</a>.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Claude model callback.
     *
     * @since    1.0.0
     */
    public function claude_model_callback() {
        $current_model = get_option('neuron_ai_claude_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_CLAUDE));
        $claude_models = ProviderConstants::getClaudeModels();
        
        ?>
        <select id="neuron_ai_claude_model" name="neuron_ai_claude_model">
            <?php foreach ($claude_models as $model_id => $model_data) : ?>
                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($current_model, $model_id); ?>>
                    <?php echo $model_data['name']; ?> - <?php echo $model_data['description']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <p class="description">
            <?php _e('Select which Claude model to use.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Gemini key callback.
     *
     * @since    1.0.0
     */
    public function gemini_key_callback() {
        $api_key = get_option('neuron_ai_gemini_key', '');
        $show_key = isset($_GET['show_keys']) && $_GET['show_keys'] === '1';
        
        ?>
        <input type="<?php echo $show_key ? 'text' : 'password'; ?>" 
               id="neuron_ai_gemini_key" 
               name="neuron_ai_gemini_key" 
               class="regular-text" 
               value="<?php echo esc_attr($api_key); ?>" />
        
        <button type="button" 
                class="button neuron-ai-toggle-key" 
                data-target="neuron_ai_gemini_key"
                data-show="<?php echo $show_key ? '1' : '0'; ?>">
            <?php echo $show_key ? __('Hide', 'neuron-ai') : __('Show', 'neuron-ai'); ?>
        </button>
        
        <p class="description">
            <?php _e('Enter your Gemini API key. <a href="https://makersuite.google.com/app/apikey" target="_blank">Get a Gemini API key</a>.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Gemini model callback.
     *
     * @since    1.0.0
     */
    public function gemini_model_callback() {
        $current_model = get_option('neuron_ai_gemini_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_GEMINI));
        $gemini_models = ProviderConstants::getGeminiModels();
        
        ?>
        <select id="neuron_ai_gemini_model" name="neuron_ai_gemini_model">
            <?php foreach ($gemini_models as $model_id => $model_data) : ?>
                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($current_model, $model_id); ?>>
                    <?php echo $model_data['name']; ?> - <?php echo $model_data['description']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <p class="description">
            <?php _e('Select which Gemini model to use.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Test connection callback.
     * 
     * @since    1.0.0
     */
    public function test_connection_callback() {
        ?>
        <button type="button" id="neuron-ai-test-claude" class="button">
            <?php _e('Test Claude Connection', 'neuron-ai'); ?>
        </button>
        
        <button type="button" id="neuron-ai-test-gemini" class="button">
            <?php _e('Test Gemini Connection', 'neuron-ai'); ?>
        </button>
        
        <p class="description">
            <?php _e('Test your API connections. Make sure to save your API keys first.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Default provider for enhancement callback.
     *
     * @since    1.0.0
     */
    public function default_provider_enhance_callback() {
        $current_provider = get_option('neuron_ai_default_provider_enhance', '');
        
        ?>
        <select id="neuron_ai_default_provider_enhance" name="neuron_ai_default_provider_enhance">
            <option value="" <?php selected($current_provider, ''); ?>><?php _e('Auto-select', 'neuron-ai'); ?></option>
            <option value="claude" <?php selected($current_provider, 'claude'); ?>><?php _e('Claude by Anthropic', 'neuron-ai'); ?></option>
            <option value="gemini" <?php selected($current_provider, 'gemini'); ?>><?php _e('Gemini by Google', 'neuron-ai'); ?></option>
        </select>
        <p class="description">
            <?php _e('Default is Claude for enhancement operations.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Default provider for search callback.
     *
     * @since    1.0.0
     */
    public function default_provider_search_callback() {
        $current_provider = get_option('neuron_ai_default_provider_search', '');
        
        ?>
        <select id="neuron_ai_default_provider_search" name="neuron_ai_default_provider_search">
            <option value="" <?php selected($current_provider, ''); ?>><?php _e('Auto-select', 'neuron-ai'); ?></option>
            <option value="claude" <?php selected($current_provider, 'claude'); ?>><?php _e('Claude by Anthropic', 'neuron-ai'); ?></option>
            <option value="gemini" <?php selected($current_provider, 'gemini'); ?>><?php _e('Gemini by Google', 'neuron-ai'); ?></option>
        </select>
        <p class="description">
            <?php _e('Default is Gemini for search operations.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Default provider for chat callback.
     *
     * @since    1.0.0
     */
    public function default_provider_chat_callback() {
        $current_provider = get_option('neuron_ai_default_provider_chat', '');
        
        ?>
        <select id="neuron_ai_default_provider_chat" name="neuron_ai_default_provider_chat">
            <option value="" <?php selected($current_provider, ''); ?>><?php _e('Auto-select', 'neuron-ai'); ?></option>
            <option value="claude" <?php selected($current_provider, 'claude'); ?>><?php _e('Claude by Anthropic', 'neuron-ai'); ?></option>
            <option value="gemini" <?php selected($current_provider, 'gemini'); ?>><?php _e('Gemini by Google', 'neuron-ai'); ?></option>
        </select>
        <p class="description">
            <?php _e('Default is Claude for chat operations.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Enable blocks callback.
     *
     * @since    1.0.0
     */
    public function enable_blocks_callback() {
        $enable_blocks = get_option('neuron_ai_enable_blocks', true);
        
        ?>
        <label>
            <input type="checkbox" id="neuron_ai_enable_blocks" name="neuron_ai_enable_blocks" value="1" <?php checked($enable_blocks); ?> />
            <?php _e('Enable Neuron AI Gutenberg blocks', 'neuron-ai'); ?>
        </label>
        <p class="description">
            <?php _e('If disabled, no Neuron AI blocks will be available in the editor.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Default tone callback.
     *
     * @since    1.0.0
     */
    public function default_tone_callback() {
        $current_tone = get_option('neuron_ai_default_tone', 'professional');
        
        ?>
        <select id="neuron_ai_default_tone" name="neuron_ai_default_tone">
            <option value="professional" <?php selected($current_tone, 'professional'); ?>><?php _e('Professional', 'neuron-ai'); ?></option>
            <option value="casual" <?php selected($current_tone, 'casual'); ?>><?php _e('Casual', 'neuron-ai'); ?></option>
            <option value="academic" <?php selected($current_tone, 'academic'); ?>><?php _e('Academic', 'neuron-ai'); ?></option>
            <option value="creative" <?php selected($current_tone, 'creative'); ?>><?php _e('Creative', 'neuron-ai'); ?></option>
        </select>
        <p class="description">
            <?php _e('Default tone for content enhancements.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Default reading level callback.
     *
     * @since    1.0.0
     */
    public function default_reading_level_callback() {
        $current_level = get_option('neuron_ai_default_reading_level', 'universal');
        
        ?>
        <select id="neuron_ai_default_reading_level" name="neuron_ai_default_reading_level">
            <option value="universal" <?php selected($current_level, 'universal'); ?>><?php _e('Universal', 'neuron-ai'); ?></option>
            <option value="simple" <?php selected($current_level, 'simple'); ?>><?php _e('Simple', 'neuron-ai'); ?></option>
            <option value="intermediate" <?php selected($current_level, 'intermediate'); ?>><?php _e('Intermediate', 'neuron-ai'); ?></option>
            <option value="advanced" <?php selected($current_level, 'advanced'); ?>><?php _e('Advanced', 'neuron-ai'); ?></option>
        </select>
        <p class="description">
            <?php _e('Default reading level for content enhancements.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Preserve tone callback.
     *
     * @since    1.0.0
     */
    public function preserve_tone_callback() {
        $preserve_tone = get_option('neuron_ai_preserve_tone', true);
        
        ?>
        <label>
            <input type="checkbox" id="neuron_ai_preserve_tone" name="neuron_ai_preserve_tone" value="1" <?php checked($preserve_tone); ?> />
            <?php _e('Analyze and preserve document tone', 'neuron-ai'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, AI will analyze your content and try to preserve its tone and style.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Default detail level callback.
     *
     * @since    1.0.0
     */
    public function default_detail_level_callback() {
        $current_level = get_option('neuron_ai_default_detail_level', 'medium');
        
        ?>
        <select id="neuron_ai_default_detail_level" name="neuron_ai_default_detail_level">
            <option value="brief" <?php selected($current_level, 'brief'); ?>><?php _e('Brief', 'neuron-ai'); ?></option>
            <option value="medium" <?php selected($current_level, 'medium'); ?>><?php _e('Medium', 'neuron-ai'); ?></option>
            <option value="comprehensive" <?php selected($current_level, 'comprehensive'); ?>><?php _e('Comprehensive', 'neuron-ai'); ?></option>
        </select>
        <p class="description">
            <?php _e('Default detail level for search results.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Include citations callback.
     *
     * @since    1.0.0
     */
    public function include_citations_callback() {
        $include_citations = get_option('neuron_ai_include_citations', true);
        
        ?>
        <label>
            <input type="checkbox" id="neuron_ai_include_citations" name="neuron_ai_include_citations" value="1" <?php checked($include_citations); ?> />
            <?php _e('Include citations in search results', 'neuron-ai'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, search results will include citation suggestions.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Context range callback.
     *
     * @since    1.0.0
     */
    public function context_range_callback() {
        $context_range = get_option('neuron_ai_context_range', 3);
        
        ?>
        <input type="number" id="neuron_ai_context_range" name="neuron_ai_context_range" value="<?php echo esc_attr($context_range); ?>" min="0" max="10" step="1" />
        <p class="description">
            <?php _e('Number of blocks to include before and after the current block for context. Higher values provide more context but may slow down performance.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Max retries callback.
     *
     * @since    1.0.0
     */
    public function max_retries_callback() {
        $max_retries = get_option('neuron_ai_max_retries', 3);
        
        ?>
        <input type="number" id="neuron_ai_max_retries" name="neuron_ai_max_retries" value="<?php echo esc_attr($max_retries); ?>" min="0" max="10" step="1" />
        <p class="description">
            <?php _e('Number of times to retry failed API requests.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Retry delay callback.
     *
     * @since    1.0.0
     */
    public function retry_delay_callback() {
        $retry_delay = get_option('neuron_ai_retry_delay', 1000);
        
        ?>
        <input type="number" id="neuron_ai_retry_delay" name="neuron_ai_retry_delay" value="<?php echo esc_attr($retry_delay); ?>" min="500" max="10000" step="100" />
        <p class="description">
            <?php _e('Delay between retry attempts in milliseconds.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Log retention callback.
     *
     * @since    1.0.0
     */
    public function log_retention_callback() {
        $log_retention = get_option('neuron_ai_log_retention', 30);
        
        ?>
        <input type="number" id="neuron_ai_log_retention" name="neuron_ai_log_retention" value="<?php echo esc_attr($log_retention); ?>" min="1" max="90" step="1" />
        <p class="description">
            <?php _e('Number of days to keep logs before automatic deletion.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Debug mode callback.
     *
     * @since    1.0.0
     */
    public function debug_mode_callback() {
        $debug_mode = get_option('neuron_ai_debug_mode', false);
        
        ?>
        <label>
            <input type="checkbox" id="neuron_ai_debug_mode" name="neuron_ai_debug_mode" value="1" <?php checked($debug_mode); ?> />
            <?php _e('Enable debug mode', 'neuron-ai'); ?>
        </label>
        <p class="description">
            <?php _e('Enables detailed logging for troubleshooting. This will significantly increase the number of logs generated.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Cache enabled callback.
     *
     * @since    1.0.0
     */
    public function cache_enabled_callback() {
        $cache_enabled = get_option('neuron_ai_cache_enabled', true);
        
        ?>
        <label>
            <input type="checkbox" id="neuron_ai_cache_enabled" name="neuron_ai_cache_enabled" value="1" <?php checked($cache_enabled); ?> />
            <?php _e('Enable caching of API responses', 'neuron-ai'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, identical API requests will be cached to improve performance and reduce API costs.', 'neuron-ai'); ?>
        </p>
        <?php
    }

    /**
     * Cache TTL callback.
     *
     * @since    1.0.0
     */
    public function cache_ttl_callback() {
        $cache_ttl = get_option('neuron_ai_cache_ttl', 3600);
        
        ?>
        <input type="number" id="neuron_ai_cache_ttl" name="neuron_ai_cache_ttl" value="<?php echo esc_attr($cache_ttl); ?>" min="60" max="86400" step="60" />
        <p class="description">
            <?php _e('How long to keep cached responses in seconds. Default is 3600 (1 hour).', 'neuron-ai'); ?>
        </p>
        <?php
    }
}