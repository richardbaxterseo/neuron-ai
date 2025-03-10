<?php
/**
 * Abstract provider class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Providers
 */

namespace NeuronAI\Providers;

use NeuronAI\Traits\ContainerAware;

/**
 * Abstract Provider class.
 *
 * Implements common functionality for all providers.
 */
abstract class AbstractProvider implements Provider {

    use ContainerAware;

    /**
     * The API key.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_key    The API key.
     */
    protected $api_key;

    /**
     * The model to use.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $model    The model identifier.
     */
    protected $model;

    /**
     * The logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      \NeuronAI\Logger    $logger    The logger instance.
     */
    protected $logger;

    /**
     * Initialize the provider.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key.
     * @param    string    $model      The model identifier (optional).
     */
    public function __construct($api_key = '', $model = '') {
        $this->api_key = $api_key;
        $this->model = $model ?: $this->getDefaultModel();
    }

    /**
     * Initialize services after container is set.
     *
     * @since    1.0.0
     */
    protected function initServices() {
        if ($this->container && !$this->logger) {
            $this->logger = $this->getService('logger');
        }
    }

    /**
     * Set the API key.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key.
     * @return   self                  The provider instance.
     */
    public function setApiKey($api_key) {
        $this->api_key = $api_key;
        return $this;
    }

    /**
     * Set the model to use.
     *
     * @since    1.0.0
     * @param    string    $model    The model identifier.
     * @return   self                The provider instance.
     */
    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    /**
     * Check if the provider has a specific capability.
     *
     * @since    1.0.0
     * @param    string    $capability    The capability to check.
     * @return   bool                     Whether the provider has the capability.
     */
    public function hasCapability($capability) {
        $capabilities = $this->getCapabilities();
        return isset($capabilities[$capability]) && $capabilities[$capability];
    }

    /**
     * Get the default model for this provider.
     *
     * @since    1.0.0
     * @return   string    The default model ID.
     */
    protected function getDefaultModel() {
        return ProviderConstants::getDefaultModel($this->getName());
    }

    /**
     * Process an error and convert it to a ProviderException.
     *
     * @since    1.0.0
     * @param    \Exception    $e              The original exception.
     * @param    string        $operation      The operation that failed.
     * @param    array         $error_data     Additional error data.
     * @return   \NeuronAI\Providers\ProviderException    The formatted exception.
     */
    protected function processError(\Exception $e, $operation, $error_data = []) {
        // Determine the appropriate error code
        $code = ProviderException::ERROR_API_REQUEST_FAILED;
        
        // Check for specific error conditions
        if (strpos($e->getMessage(), 'api_key') !== false || strpos($e->getMessage(), 'API key') !== false) {
            $code = ProviderException::ERROR_MISSING_API_KEY;
        } elseif (strpos($e->getMessage(), 'rate limit') !== false || $e->getCode() == 429) {
            $code = ProviderException::ERROR_RATE_LIMIT_EXCEEDED;
        } elseif (strpos($e->getMessage(), 'model') !== false && strpos($e->getMessage(), 'exist') !== false) {
            $code = ProviderException::ERROR_INVALID_MODEL;
        }
        
        // If the error is already a ProviderException, return it
        if ($e instanceof ProviderException) {
            return $e;
        }
        
        // Format the message with operation context
        $message = sprintf(
            __('Error during %s operation: %s', 'neuron-ai'),
            $operation,
            $e->getMessage()
        );
        
        // Create and return the exception
        return new ProviderException(
            $message,
            $code,
            $e,
            $error_data
        );
    }

    /**
     * Get retry settings for API requests.
     *
     * @since    1.0.0
     * @return   array    Retry settings including count and delay.
     */
    protected function getRetrySettings() {
        return [
            'max_retries' => get_option('neuron_ai_max_retries', 3),
            'retry_delay' => get_option('neuron_ai_retry_delay', 1000),
        ];
    }

    /**
     * Execute API request with retry logic.
     *
     * @since    1.0.0
     * @param    callable    $request_function    Function that performs the API request.
     * @param    string      $operation           Operation name for logging.
     * @return   mixed                            The response data.
     * @throws   \NeuronAI\Providers\ProviderException    If all retry attempts fail.
     */
    protected function executeWithRetry(callable $request_function, $operation) {
        $retry_settings = $this->getRetrySettings();
        $max_retries = $retry_settings['max_retries'];
        $retry_delay = $retry_settings['retry_delay']; // in milliseconds
        
        $attempts = 0;
        $last_exception = null;
        
        while ($attempts <= $max_retries) {
            try {
                return $request_function();
            } catch (\Exception $e) {
                $last_exception = $e;
                $attempts++;
                
                // If this is a provider exception and it's not rate limited, don't retry
                if ($e instanceof ProviderException && !$e->isRateLimit()) {
                    break;
                }
                
                // If we've reached max retries, give up
                if ($attempts > $max_retries) {
                    break;
                }
                
                // Log the retry
                if ($this->logger) {
                    $this->logger->log_request('retry', [
                        'provider' => $this->getName(),
                        'operation' => $operation,
                        'attempt' => $attempts,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Wait before retrying
                usleep($retry_delay * 1000);
            }
        }
        
        // If we get here, all retries failed
        return $this->processError(
            $last_exception,
            $operation,
            ['attempts' => $attempts]
        );
    }
}