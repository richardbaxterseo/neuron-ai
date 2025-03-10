<?php
/**
 * Provide an about page for the plugin
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
            <p><?php _e('Information about Neuron AI plugin and its capabilities.', 'neuron-ai'); ?></p>
        </div>
    </div>
    
    <div class="neuron-ai-about-container">
        <div class="neuron-ai-about-section">
            <h2><?php _e('About Neuron AI', 'neuron-ai'); ?></h2>
            <p><?php _e('Neuron AI integrates advanced AI capabilities from Anthropic Claude and Google Gemini directly into your WordPress content creation workflow. Create, enhance, and research content without leaving the WordPress editor.', 'neuron-ai'); ?></p>
            
            <p><?php _e('Version', 'neuron-ai'); ?>: <strong><?php echo NEURON_AI_VERSION; ?></strong></p>
        </div>
        
        <div class="neuron-ai-about-section">
            <h2><?php _e('Features', 'neuron-ai'); ?></h2>
            <div class="neuron-ai-features-grid">
                <div class="neuron-ai-feature-card">
                    <h3><?php _e('Content Enhancement', 'neuron-ai'); ?></h3>
                    <p><?php _e('Enhance your content with AI while maintaining your unique tone and style. Improve clarity, fix grammar, and make your content more engaging.', 'neuron-ai'); ?></p>
                </div>
                
                <div class="neuron-ai-feature-card">
                    <h3><?php _e('AI-Powered Research', 'neuron-ai'); ?></h3>
                    <p><?php _e('Research topics directly within WordPress. Get comprehensive information with optional citations, all formatted and ready to use.', 'neuron-ai'); ?></p>
                </div>
                
                <div class="neuron-ai-feature-card">
                    <h3><?php _e('Conversation Mode', 'neuron-ai'); ?></h3>
                    <p><?php _e('Have interactive conversations with AI to refine your content. Ask questions, request revisions, and brainstorm ideas without leaving WordPress.', 'neuron-ai'); ?></p>
                </div>
                
                <div class="neuron-ai-feature-card">
                    <h3><?php _e('Context-Aware AI', 'neuron-ai'); ?></h3>
                    <p><?php _e('AI understands your entire page context, providing enhancements and responses that fit seamlessly with your existing content.', 'neuron-ai'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="neuron-ai-about-section">
            <h2><?php _e('Gutenberg Blocks', 'neuron-ai'); ?></h2>
            <div class="neuron-ai-blocks-info">
                <div class="neuron-ai-block-info">
                    <h3><?php _e('Neuron Text Block', 'neuron-ai'); ?></h3>
                    <p><?php _e('A rich text block with AI enhancement capabilities. Write content as you normally would, then enhance it with AI assistance.', 'neuron-ai'); ?></p>
                    <ul>
                        <li><?php _e('Context-aware enhancements', 'neuron-ai'); ?></li>
                        <li><?php _e('Tone and reading level preservation', 'neuron-ai'); ?></li>
                        <li><?php _e('Conversation mode for interactive content creation', 'neuron-ai'); ?></li>
                    </ul>
                </div>
                
                <div class="neuron-ai-block-info">
                    <h3><?php _e('Neuron Search Block', 'neuron-ai'); ?></h3>
                    <p><?php _e('A research block for finding information on specific topics. Perfect for research-based content.', 'neuron-ai'); ?></p>
                    <ul>
                        <li><?php _e('Configurable detail level', 'neuron-ai'); ?></li>
                        <li><?php _e('Citation options', 'neuron-ai'); ?></li>
                        <li><?php _e('Clean HTML output ready for publishing', 'neuron-ai'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="neuron-ai-about-section">
            <h2><?php _e('Getting Started', 'neuron-ai'); ?></h2>
            <ol>
                <li><?php _e('Configure your API keys in the <strong>API Keys</strong> tab.', 'neuron-ai'); ?></li>
                <li><?php _e('Customize your content preferences in the <strong>Content</strong> tab.', 'neuron-ai'); ?></li>
                <li><?php _e('Open the WordPress editor and look for the Neuron AI blocks in the block inserter.', 'neuron-ai'); ?></li>
                <li><?php _e('Start creating AI-enhanced content!', 'neuron-ai'); ?></li>
            </ol>
            
            <p><?php _e('Need help? Check out our <a href="#" target="_blank">documentation</a> for detailed instructions and tips.', 'neuron-ai'); ?></p>
        </div>
        
        <div class="neuron-ai-about-section">
            <h2><?php _e('Credits', 'neuron-ai'); ?></h2>
            <p><?php _e('Neuron AI is built on top of the <a href="https://github.com/inspector-apm/neuron-ai" target="_blank">Neuron AI PHP Framework</a> and integrates with:', 'neuron-ai'); ?></p>
            <ul>
                <li><?php _e('<a href="https://www.anthropic.com/" target="_blank">Anthropic Claude</a> - Advanced AI assistant for content enhancement and conversation', 'neuron-ai'); ?></li>
                <li><?php _e('<a href="https://makersuite.google.com/" target="_blank">Google Gemini</a> - Powerful AI model for information retrieval and search', 'neuron-ai'); ?></li>
            </ul>
        </div>
    </div>
</div>