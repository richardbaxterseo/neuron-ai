<?php
/**
 * Provider Factory class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Providers
 */

namespace NeuronAI\Providers;

use NeuronAI\Traits\ContainerAware;

/**
 * Provider Factory class.
 *
 * Creates provider instances based on name or required capability.
 */
class ProviderFactory {

    use ContainerAware;

    /**
     * Get a provider instance by name.
     *
     * @since    1.0.0
     * @param    string    $provider_name    The provider name.
     * @param    string    $api_key          The API key (optional).
     * @param    string    $model            The model to use (optional).
     * @return   Provider                    The provider instance.
     * @throws   \NeuronAI\Providers\ProviderException    If provider initialization fails.
     */
    public function getProvider($provider_name, $api_key = '', $model = '') {
        switch ($provider_name) {
            case ProviderConstants::PROVIDER_CLAUDE:
                $provider = new ClaudeProvider($api_key, $model);
                break;
            
            case ProviderConstants::PROVIDER_GEMINI:
                $provider = new GeminiProvider($api_key, $model);
                break;
            
            default:
                throw new ProviderException(
                    sprintf('Provider "%s" is not supported.', $provider_name),
                    ProviderException::ERROR_INITIALIZATION_FAILED
                );
        }
        
        // Set container for dependency injection
        if ($this->container) {
            $provider->setContainer($this->container);
        }
        
        return $provider;
    }

    /**
     * Get a provider instance for a specific capability.
     *
     * @since    1.0.0
     * @param    string    $capability      The required capability.
     * @param    string    $preferred_provider    Preferred provider name (optional).
     * @return   Provider                   The provider instance.
     * @throws   \NeuronAI\Providers\ProviderException    If no provider with the capability is available.
     */
    public function getProviderForCapability($capability, $preferred_provider = '') {
        // Get settings from options
        $claude_api_key = get_option('neuron_ai_anthropic_key', '');
        $claude_model = get_option('neuron_ai_claude_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_CLAUDE));
        
        // Get default provider for capability from settings
        $default_provider = get_option('neuron_ai_default_provider_' . $capability, '');
        
        // Determine which provider to use
        $provider_name = $preferred_provider ?: $default_provider;
        
        // If no specific provider is selected, choose based on capability
        if (!$provider_name) {
            // For search capability, prefer Gemini if implemented
            if ($capability === ProviderConstants::CAPABILITY_SEARCH) {
                $provider_name = ProviderConstants::PROVIDER_GEMINI;
            } else {
                $provider_name = ProviderConstants::PROVIDER_CLAUDE;
            }
        }
        
        try {
            // Try to create the preferred provider
            switch ($provider_name) {
                case ProviderConstants::PROVIDER_CLAUDE:
                    $provider = new ClaudeProvider($claude_api_key, $claude_model);
                    break;
                
                case ProviderConstants::PROVIDER_GEMINI:
                    $gemini_api_key = get_option('neuron_ai_gemini_key', '');
                    $gemini_model = get_option('neuron_ai_gemini_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_GEMINI));
                    $provider = new GeminiProvider($gemini_api_key, $gemini_model);
                    break;
                
                default:
                    // Default to Claude if preferred provider is not recognized
                    $provider = new ClaudeProvider($claude_api_key, $claude_model);
            }
            
            // Set container for dependency injection
            if ($this->container) {
                $provider->setContainer($this->container);
            }
            
            // Check if the provider supports the required capability
            if (!$provider->hasCapability($capability)) {
                throw new ProviderException(
                    sprintf('Provider "%s" does not support the "%s" capability.', $provider->getName(), $capability),
                    ProviderException::ERROR_CAPABILITY_NOT_SUPPORTED
                );
            }
            
            return $provider;
        } catch (ProviderException $e) {
            // If the preferred provider fails, try to fall back to an alternative
            if ($provider_name !== ProviderConstants::PROVIDER_CLAUDE) {
                try {
                    $fallback_provider = new ClaudeProvider($claude_api_key, $claude_model);
                    
                    if ($this->container) {
                        $fallback_provider->setContainer($this->container);
                    }
                    
                    if ($fallback_provider->hasCapability($capability)) {
                        return $fallback_provider;
                    }
                } catch (\Exception $fallback_e) {
                    // If fallback also fails, throw the original exception
                }
            }
            
            // If no provider works, throw the original exception
            throw $e;
        }
    }

    /**
     * Get all available providers.
     *
     * @since    1.0.0
     * @return   array    Array of provider instances.
     */
    public function getAllProviders() {
        $providers = [];
        
        // Add Claude provider
        $claude_api_key = get_option('neuron_ai_anthropic_key', '');
        $claude_model = get_option('neuron_ai_claude_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_CLAUDE));
        
        try {
            $claude_provider = new ClaudeProvider($claude_api_key, $claude_model);
            
            if ($this->container) {
                $claude_provider->setContainer($this->container);
            }
            
            $providers[ProviderConstants::PROVIDER_CLAUDE] = $claude_provider;
        } catch (\Exception $e) {
            // Skip if initialization fails
        }
        
        // Add Gemini provider
        $gemini_api_key = get_option('neuron_ai_gemini_key', '');
        $gemini_model = get_option('neuron_ai_gemini_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_GEMINI));
        
        try {
            $gemini_provider = new GeminiProvider($gemini_api_key, $gemini_model);
            
            if ($this->container) {
                $gemini_provider->setContainer($this->container);
            }
            
            $providers[ProviderConstants::PROVIDER_GEMINI] = $gemini_provider;
        } catch (\Exception $e) {
            // Skip if initialization fails
        }
        
        return $providers;
    }

    /**
     * Get status information for all providers.
     *
     * @since    1.0.0
     * @return   array    Array of provider status information.
     */
    public function getProvidersStatus() {
        $status = [];
        
        // Get Claude status
        $claude_api_key = get_option('neuron_ai_anthropic_key', '');
        $claude_model = get_option('neuron_ai_claude_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_CLAUDE));
        
        $claude_status = [
            'name' => __('Claude by Anthropic', 'neuron-ai'),
            'has_api_key' => !empty($claude_api_key),
            'model' => $claude_model,
            'model_name' => ProviderConstants::getModelDetails(ProviderConstants::PROVIDER_CLAUDE, $claude_model)['name'] ?? '',
            'connected' => false,
            'capabilities' => []
        ];
        
        if (!empty($claude_api_key)) {
            try {
                $claude_provider = new ClaudeProvider($claude_api_key, $claude_model);
                
                if ($this->container) {
                    $claude_provider->setContainer($this->container);
                }
                
                $connection_test = $claude_provider->testConnection();
                $claude_status['connected'] = $connection_test['success'];
                $claude_status['message'] = $connection_test['message'] ?? '';
                $claude_status['capabilities'] = $claude_provider->getCapabilities();
            } catch (\Exception $e) {
                $claude_status['connected'] = false;
                $claude_status['message'] = $e->getMessage();
            }
        }
        
        $status[ProviderConstants::PROVIDER_CLAUDE] = $claude_status;
        
        // Add Gemini status
        $gemini_api_key = get_option('neuron_ai_gemini_key', '');
        $gemini_model = get_option('neuron_ai_gemini_model', ProviderConstants::getDefaultModel(ProviderConstants::PROVIDER_GEMINI));
        
        $gemini_status = [
            'name' => __('Gemini by Google', 'neuron-ai'),
            'has_api_key' => !empty($gemini_api_key),
            'model' => $gemini_model,
            'model_name' => ProviderConstants::getModelDetails(ProviderConstants::PROVIDER_GEMINI, $gemini_model)['name'] ?? '',
            'connected' => false,
            'capabilities' => []
        ];
        
        if (!empty($gemini_api_key)) {
            try {
                $gemini_provider = new GeminiProvider($gemini_api_key, $gemini_model);
                
                if ($this->container) {
                    $gemini_provider->setContainer($this->container);
                }
                
                $connection_test = $gemini_provider->testConnection();
                $gemini_status['connected'] = $connection_test['success'];
                $gemini_status['message'] = $connection_test['message'] ?? '';
                $gemini_status['capabilities'] = $gemini_provider->getCapabilities();
            } catch (\Exception $e) {
                $gemini_status['connected'] = false;
                $gemini_status['message'] = $e->getMessage();
            }
        }
        
        $status[ProviderConstants::PROVIDER_GEMINI] = $gemini_status;
        
        return $status;
    }
}