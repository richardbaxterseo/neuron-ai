<?php
/**
 * Retry Handler utility class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Utils
 */

namespace NeuronAI\Utils;

use NeuronAI\Providers\ProviderException;

/**
 * Retry Handler class.
 *
 * Provides retry functionality for API requests to handle transient errors.
 */
class RetryHandler {

    /**
     * Maximum number of retry attempts.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_retries    Maximum number of retry attempts.
     */
    private $max_retries;

    /**
     * Delay between retries in milliseconds.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $retry_delay    Delay between retries in milliseconds.
     */
    private $retry_delay;

    /**
     * List of error codes that should be retried.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $retryable_codes    List of error codes that should be retried.
     */
    private $retryable_codes = [
        ProviderException::ERROR_API_REQUEST_FAILED,
        ProviderException::ERROR_API_RESPONSE_ERROR,
        502, // Bad Gateway
        503, // Service Unavailable
        504, // Gateway Timeout
        429, // Too Many Requests (rate limit)
    ];

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Logger    $logger    Logger instance.
     */
    private $logger;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    int       $max_retries     Maximum number of retry attempts (default: 3).
     * @param    int       $retry_delay     Delay between retries in milliseconds (default: 1000).
     * @param    array     $retryable_codes Additional error codes that should be retried.
     * @param    object    $logger          Logger instance (optional).
     */
    public function __construct($max_retries = null, $retry_delay = null, $retryable_codes = [], $logger = null) {
        // Use settings or defaults
        $this->max_retries = $max_retries ?? get_option('neuron_ai_max_retries', 3);
        $this->retry_delay = $retry_delay ?? get_option('neuron_ai_retry_delay', 1000);
        
        // Add additional retryable codes
        if (!empty($retryable_codes)) {
            $this->retryable_codes = array_merge($this->retryable_codes, $retryable_codes);
        }
        
        $this->logger = $logger;
    }

    /**
     * Execute a function with retry logic.
     *
     * @since    1.0.0
     * @param    callable  $function    The function to execute.
     * @param    array     $args        Arguments to pass to the function.
     * @return   mixed                  The result of the function.
     * @throws   \Exception             The last exception thrown if all retries fail.
     */
    public function execute(callable $function, array $args = []) {
        $attempts = 0;
        $last_exception = null;
        
        do {
            try {
                // Attempt to execute the function
                return call_user_func_array($function, $args);
            } catch (ProviderException $e) {
                $last_exception = $e;
                
                // Check if the error is retryable
                if (!$this->isRetryable($e)) {
                    // If not retryable, rethrow immediately
                    throw $e;
                }
                
                $attempts++;
                
                // Log the retry attempt
                if ($this->logger) {
                    $this->logger->log_error(
                        $e->getCode(),
                        sprintf('Retry attempt %d of %d: %s', $attempts, $this->max_retries, $e->getMessage()),
                        ['retryable' => true, 'attempt' => $attempts]
                    );
                }
                
                // If we still have retries left, wait before trying again
                if ($attempts < $this->max_retries) {
                    $this->wait($attempts);
                }
            } catch (\Exception $e) {
                // For other exceptions, just rethrow
                throw $e;
            }
        } while ($attempts < $this->max_retries);
        
        // If we've exhausted retries, throw the last exception
        throw $last_exception;
    }

    /**
     * Check if an exception is retryable.
     *
     * @since    1.0.0
     * @param    \Exception    $e    The exception to check.
     * @return   bool                Whether the exception is retryable.
     */
    public function isRetryable($e) {
        // Check if the exception is a ProviderException with a retryable code
        if ($e instanceof ProviderException) {
            return in_array($e->getCode(), $this->retryable_codes);
        }
        
        // For other exceptions, check if the code is in the list of retryable codes
        return in_array($e->getCode(), $this->retryable_codes);
    }

    /**
     * Wait between retries with exponential backoff.
     *
     * @since    1.0.0
     * @param    int    $attempt    The current attempt number.
     */
    private function wait($attempt) {
        // Calculate delay with exponential backoff and jitter
        $delay = $this->retry_delay * pow(2, $attempt - 1);
        
        // Add jitter (random variance) to prevent thundering herd
        $jitter = mt_rand(0, (int)($delay * 0.1));
        $delay += $jitter;
        
        // Cap the delay at 30 seconds
        $delay = min($delay, 30000);
        
        // Convert milliseconds to microseconds for usleep
        usleep($delay * 1000);
    }

    /**
     * Set maximum number of retries.
     *
     * @since    1.0.0
     * @param    int    $max_retries    Maximum number of retry attempts.
     * @return   self                   The current instance.
     */
    public function setMaxRetries($max_retries) {
        $this->max_retries = $max_retries;
        return $this;
    }

    /**
     * Set delay between retries.
     *
     * @since    1.0.0
     * @param    int    $retry_delay    Delay between retries in milliseconds.
     * @return   self                   The current instance.
     */
    public function setRetryDelay($retry_delay) {
        $this->retry_delay = $retry_delay;
        return $this;
    }

    /**
     * Set retryable error codes.
     *
     * @since    1.0.0
     * @param    array    $retryable_codes    List of error codes that should be retried.
     * @return   self                         The current instance.
     */
    public function setRetryableCodes($retryable_codes) {
        $this->retryable_codes = $retryable_codes;
        return $this;
    }
    
    /**
     * Set logger instance.
     *
     * @since    1.0.0
     * @param    object    $logger    Logger instance.
     * @return   self                 The current instance.
     */
    public function setLogger($logger) {
        $this->logger = $logger;
        return $this;
    }
    
    /**
     * Get maximum number of retries.
     *
     * @since    1.0.0
     * @return   int    Maximum number of retry attempts.
     */
    public function getMaxRetries() {
        return $this->max_retries;
    }
    
    /**
     * Get delay between retries.
     *
     * @since    1.0.0
     * @return   int    Delay between retries in milliseconds.
     */
    public function getRetryDelay() {
        return $this->retry_delay;
    }
    
    /**
     * Get retryable error codes.
     *
     * @since    1.0.0
     * @return   array    List of error codes that should be retried.
     */
    public function getRetryableCodes() {
        return $this->retryable_codes;
    }
}