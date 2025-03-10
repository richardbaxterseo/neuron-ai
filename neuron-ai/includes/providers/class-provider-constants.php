<?php
/**
 * Provider constants class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Providers
 */

namespace NeuronAI\Providers;

/**
 * Provider constants class.
 *
 * Defines common constants used by providers.
 */
class ProviderConstants {

    /**
     * Provider capability constants.
     */
    const CAPABILITY_ENHANCE = 'enhance';
    const CAPABILITY_SEARCH = 'search';
    const CAPABILITY_CHAT = 'chat';
    const CAPABILITY_VISION = 'vision';
    const CAPABILITY_EMBED = 'embed';

    /**
     * Provider names.
     */
    const PROVIDER_CLAUDE = 'claude';
    const PROVIDER_GEMINI = 'gemini';

    /**
     * Model types.
     */
    const MODEL_TYPE_TEXT = 'text';
    const MODEL_TYPE_CHAT = 'chat';
    const MODEL_TYPE_VISION = 'vision';
    const MODEL_TYPE_EMBED = 'embed';

    /**
     * Claude models.
     *
     * @since    1.0.0
     * @return   array    Available Claude models.
     */
    public static function getClaudeModels() {
        return [
            'claude-3-opus-20240229' => [
                'name' => 'Claude 3 Opus',
                'description' => __('Most powerful model for complex tasks', 'neuron-ai'),
                'type' => self::MODEL_TYPE_CHAT,
                'token_limit' => 200000,
                'capabilities' => [
                    self::CAPABILITY_ENHANCE => true,
                    self::CAPABILITY_CHAT => true,
                    self::CAPABILITY_VISION => true,
                ],
            ],
            'claude-3-sonnet-20240229' => [
                'name' => 'Claude 3 Sonnet',
                'description' => __('Balanced model for most tasks', 'neuron-ai'),
                'type' => self::MODEL_TYPE_CHAT,
                'token_limit' => 180000,
                'capabilities' => [
                    self::CAPABILITY_ENHANCE => true,
                    self::CAPABILITY_CHAT => true,
                    self::CAPABILITY_VISION => true,
                ],
            ],
            'claude-3-haiku-20240307' => [
                'name' => 'Claude 3 Haiku',
                'description' => __('Fastest and most cost-effective model', 'neuron-ai'),
                'type' => self::MODEL_TYPE_CHAT,
                'token_limit' => 160000,
                'capabilities' => [
                    self::CAPABILITY_ENHANCE => true,
                    self::CAPABILITY_CHAT => true,
                    self::CAPABILITY_VISION => true,
                ],
            ],
        ];
    }

    /**
     * Gemini models.
     *
     * @since    1.0.0
     * @return   array    Available Gemini models.
     */
    public static function getGeminiModels() {
        return [
            'gemini-pro' => [
                'name' => 'Gemini Pro',
                'description' => __('Balanced model for most tasks', 'neuron-ai'),
                'type' => self::MODEL_TYPE_CHAT,
                'token_limit' => 32000,
                'capabilities' => [
                    self::CAPABILITY_ENHANCE => true,
                    self::CAPABILITY_SEARCH => true,
                    self::CAPABILITY_CHAT => true,
                ],
            ],
            'gemini-pro-vision' => [
                'name' => 'Gemini Pro Vision',
                'description' => __('Enhanced model with vision capabilities', 'neuron-ai'),
                'type' => self::MODEL_TYPE_VISION,
                'token_limit' => 16000,
                'capabilities' => [
                    self::CAPABILITY_ENHANCE => true,
                    self::CAPABILITY_SEARCH => true,
                    self::CAPABILITY_CHAT => true,
                    self::CAPABILITY_VISION => true,
                ],
            ],
        ];
    }

    /**
     * Get all available models.
     *
     * @since    1.0.0
     * @return   array    All available models indexed by provider.
     */
    public static function getAllModels() {
        return [
            self::PROVIDER_CLAUDE => self::getClaudeModels(),
            self::PROVIDER_GEMINI => self::getGeminiModels(),
        ];
    }

    /**
     * Get default model for a provider.
     *
     * @since    1.0.0
     * @param    string    $provider    The provider name.
     * @return   string                 The default model ID.
     */
    public static function getDefaultModel($provider) {
        switch ($provider) {
            case self::PROVIDER_CLAUDE:
                return 'claude-3-sonnet-20240229';
            case self::PROVIDER_GEMINI:
                return 'gemini-pro';
            default:
                return '';
        }
    }

    /**
     * Get capabilities for a specific model.
     *
     * @since    1.0.0
     * @param    string    $provider    The provider name.
     * @param    string    $model_id    The model ID.
     * @return   array                  The model capabilities.
     */
    public static function getModelCapabilities($provider, $model_id) {
        $all_models = self::getAllModels();
        
        if (!isset($all_models[$provider]) || !isset($all_models[$provider][$model_id])) {
            return [];
        }
        
        return $all_models[$provider][$model_id]['capabilities'] ?? [];
    }

    /**
     * Get model details.
     *
     * @since    1.0.0
     * @param    string    $provider    The provider name.
     * @param    string    $model_id    The model ID.
     * @return   array                  The model details.
     */
    public static function getModelDetails($provider, $model_id) {
        $all_models = self::getAllModels();
        
        if (!isset($all_models[$provider]) || !isset($all_models[$provider][$model_id])) {
            return [];
        }
        
        return $all_models[$provider][$model_id];
    }
}