<?php
/**
 * Provider Exception class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Providers
 */

namespace NeuronAI\Providers;

/**
 * Provider Exception class.
 *
 * Custom exception for provider errors with user-friendly messages.
 */
class ProviderException extends \Exception {

    /**
     * Error constants.
     */
    // Configuration errors
    const ERROR_MISSING_API_KEY = 1001;
    const ERROR_INVALID_MODEL = 1002;
    const ERROR_INITIALIZATION_FAILED = 1003;
    
    // Request errors
    const ERROR_INVALID_REQUEST = 2001;
    const ERROR_UNAUTHORIZED = 2002;
    const ERROR_RATE_LIMIT_EXCEEDED = 2003;
    const ERROR_API_REQUEST_FAILED = 2004;
    const ERROR_API_RESPONSE_ERROR = 2005;
    
    // Capability errors
    const ERROR_CAPABILITY_NOT_SUPPORTED = 3001;
    
    /**
     * Original exception if any.
     *
     * @since    1.0.0
     * @access   private
     * @var      \Exception    $previous    Original exception.
     */
    private $previous;

    /**
     * Technical details about the error.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $technical_details    Technical details.
     */
    private $technical_details = [];

    /**
     * User-friendly message.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $user_message    User-friendly message.
     */
    private $user_message;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string       $message            Error message.
     * @param    int          $code               Error code.
     * @param    \Exception   $previous           Previous exception (optional).
     * @param    array        $technical_details  Technical details (optional).
     */
    public function __construct($message, $code, \Exception $previous = null, array $technical_details = []) {
        parent::__construct($message, $code, $previous);
        
        $this->previous = $previous;
        $this->technical_details = $technical_details;
        
        // Set user-friendly message based on error code
        $this->setUserMessage();
    }

    /**
     * Set user-friendly message based on error code.
     *
     * @since    1.0.0
     */
    private function setUserMessage() {
        switch ($this->code) {
            case self::ERROR_MISSING_API_KEY:
                $this->user_message = __('API key is missing or not configured.', 'neuron-ai');
                break;
                
            case self::ERROR_INVALID_MODEL:
                $this->user_message = __('Invalid AI model specified.', 'neuron-ai');
                break;
                
            case self::ERROR_INITIALIZATION_FAILED:
                $this->user_message = __('Failed to initialize AI service.', 'neuron-ai');
                break;
                
            case self::ERROR_INVALID_REQUEST:
                $this->user_message = __('Invalid request parameters.', 'neuron-ai');
                break;
                
            case self::ERROR_UNAUTHORIZED:
                $this->user_message = __('API authorization failed.', 'neuron-ai');
                break;
                
            case self::ERROR_RATE_LIMIT_EXCEEDED:
                $this->user_message = __('API rate limit exceeded.', 'neuron-ai');
                break;
                
            case self::ERROR_API_REQUEST_FAILED:
                $this->user_message = __('Failed to communicate with AI service.', 'neuron-ai');
                break;
                
            case self::ERROR_API_RESPONSE_ERROR:
                $this->user_message = __('AI service returned an error.', 'neuron-ai');
                break;
                
            case self::ERROR_CAPABILITY_NOT_SUPPORTED:
                $this->user_message = __('This feature is not supported by the selected AI model.', 'neuron-ai');
                break;
                
            default:
                $this->user_message = __('An unexpected error occurred with the AI service.', 'neuron-ai');
        }
    }

    /**
     * Get user-friendly message.
     *
     * @since    1.0.0
     * @return   string    User-friendly message.
     */
    public function getUserMessage() {
        return $this->user_message;
    }

    /**
     * Get technical details.
     *
     * @since    1.0.0
     * @return   array    Technical details.
     */
    public function getTechnicalDetails() {
        $details = $this->technical_details;
        
        // Add standard details
        $details['error_message'] = $this->getMessage();
        $details['error_code'] = $this->getCode();
        
        // Add previous exception details if available
        if ($this->previous instanceof \Exception) {
            $details['previous_error'] = [
                'message' => $this->previous->getMessage(),
                'code' => $this->previous->getCode(),
                'file' => $this->previous->getFile(),
                'line' => $this->previous->getLine(),
            ];
            
            // Special handling for API-specific exceptions
            if (isset($this->previous->response) && is_array($this->previous->response)) {
                $details['api_response'] = $this->previous->response;
            }
        }
        
        return $details;
    }
}