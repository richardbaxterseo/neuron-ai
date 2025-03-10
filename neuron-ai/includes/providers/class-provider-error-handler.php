<?php
/**
 * Provider Error Handler class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Providers
 */

namespace NeuronAI\Providers;

use NeuronAI\Traits\ContainerAware;

/**
 * Provider Error Handler class.
 *
 * Handles errors from providers in a consistent way.
 */
class ProviderErrorHandler {

    use ContainerAware;

    /**
     * The logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Logger    $logger    The logger instance.
     */
    private $logger;

    /**
     * Initialize services after container is set.
     *
     * @since    1.0.0
     */
    private function initServices() {
        if ($this->container && !$this->logger) {
            $this->logger = $this->getService('logger');
        }
    }

    /**
     * Handle provider exception and format response.
     *
     * @since    1.0.0
     * @param    \NeuronAI\Providers\ProviderException    $e              The provider exception.
     * @param    bool                                     $show_details   Whether to include technical details.
     * @return   array                                    Formatted error response.
     */
    public function handleException(ProviderException $e, $show_details = false) {
        $this->initServices();
        
        // Log the error
        if ($this->logger) {
            $this->logger->log_error($e->getCode(), $e->getMessage(), $e->getTechnicalDetails());
        }
        
        // Format the response
        $response = [
            'success' => false,
            'message' => $e->getUserMessage(),
            'code' => $e->getCode()
        ];
        
        // Add technical details if requested and user has appropriate permissions
        if ($show_details && current_user_can('manage_options')) {
            $response['details'] = $e->getTechnicalDetails();
        }
        
        return $response;
    }

    /**
     * Handle provider error response for REST API.
     *
     * @since    1.0.0
     * @param    \NeuronAI\Providers\ProviderException    $e              The provider exception.
     * @param    int                                      $status_code    HTTP status code (optional).
     * @return   \WP_REST_Response                        WordPress REST response.
     */
    public function handleRestException(ProviderException $e, $status_code = null) {
        $response = $this->handleException($e, true);
        
        // Determine appropriate status code based on error type
        if ($status_code === null) {
            switch ($e->getCode()) {
                case ProviderException::ERROR_MISSING_API_KEY:
                case ProviderException::ERROR_INVALID_MODEL:
                case ProviderException::ERROR_INVALID_REQUEST:
                    $status_code = 400; // Bad Request
                    break;
                case ProviderException::ERROR_UNAUTHORIZED:
                    $status_code = 401; // Unauthorized
                    break;
                case ProviderException::ERROR_RATE_LIMIT_EXCEEDED:
                    $status_code = 429; // Too Many Requests
                    break;
                case ProviderException::ERROR_API_REQUEST_FAILED:
                case ProviderException::ERROR_API_RESPONSE_ERROR:
                    $status_code = 502; // Bad Gateway
                    break;
                case ProviderException::ERROR_CAPABILITY_NOT_SUPPORTED:
                    $status_code = 501; // Not Implemented
                    break;
                default:
                    $status_code = 500; // Internal Server Error
            }
        }
        
        return new \WP_REST_Response($response, $status_code);
    }

    /**
     * Format user-friendly error message for the admin UI.
     *
     * @since    1.0.0
     * @param    \NeuronAI\Providers\ProviderException    $e    The provider exception.
     * @return   string                                          HTML formatted error message.
     */
    public function formatAdminErrorMessage(ProviderException $e) {
        $message = '<div class="neuron-ai-error notice notice-error">';
        $message .= '<p><strong>' . __('AI Error:', 'neuron-ai') . '</strong> ' . esc_html($e->getUserMessage()) . '</p>';
        
        // Add troubleshooting tips based on error code
        switch ($e->getCode()) {
            case ProviderException::ERROR_MISSING_API_KEY:
                $message .= '<p>' . sprintf(
                    __('Please <a href="%s">check your API key settings</a> to ensure they are configured correctly.', 'neuron-ai'),
                    admin_url('admin.php?page=neuron-ai-settings')
                ) . '</p>';
                break;
                
            case ProviderException::ERROR_RATE_LIMIT_EXCEEDED:
                $message .= '<p>' . __('Your API request rate limit has been exceeded. Try again in a few minutes or consider upgrading your API plan.', 'neuron-ai') . '</p>';
                break;
                
            case ProviderException::ERROR_CAPABILITY_NOT_SUPPORTED:
                $message .= '<p>' . __('The selected AI model does not support this feature. Try changing the model in your settings.', 'neuron-ai') . '</p>';
                break;
                
            case ProviderException::ERROR_API_REQUEST_FAILED:
                $message .= '<p>' . __('There was an error communicating with the AI service. Please check your internet connection and try again later.', 'neuron-ai') . '</p>';
                break;
                
            case ProviderException::ERROR_UNAUTHORIZED:
                $message .= '<p>' . __('Your API key was rejected by the service. Please check that your API key is valid and has not expired.', 'neuron-ai') . '</p>';
                break;
                
            case ProviderException::ERROR_INVALID_MODEL:
                $message .= '<p>' . __('The selected AI model is not valid or unavailable. Please select a different model in the settings.', 'neuron-ai') . '</p>';
                break;
        }
        
        // Add technical details for admin users
        if (current_user_can('manage_options')) {
            $tech_details = $e->getTechnicalDetails();
            
            $message .= '<div class="neuron-ai-error-details">';
            $message .= '<p><strong>' . __('Technical Details (visible to admins only):', 'neuron-ai') . '</strong></p>';
            $message .= '<pre>' . esc_html(json_encode($tech_details, JSON_PRETTY_PRINT)) . '</pre>';
            $message .= '</div>';
        }
        
        $message .= '</div>';
        
        return $message;
    }

    /**
     * Format error message for front-end display.
     *
     * @since    1.0.0
     * @param    \NeuronAI\Providers\ProviderException    $e    The provider exception.
     * @return   string                                          HTML formatted error message.
     */
    public function formatPublicErrorMessage(ProviderException $e) {
        $message = '<div class="neuron-ai-error">';
        $message .= '<p>' . esc_html($e->getUserMessage()) . '</p>';
        
        // Add simple troubleshooting tips for non-admins
        switch ($e->getCode()) {
            case ProviderException::ERROR_RATE_LIMIT_EXCEEDED:
                $message .= '<p>' . __('Please try again in a few minutes.', 'neuron-ai') . '</p>';
                break;
                
            case ProviderException::ERROR_API_REQUEST_FAILED:
            case ProviderException::ERROR_API_RESPONSE_ERROR:
                $message .= '<p>' . __('Please try again or contact the site administrator.', 'neuron-ai') . '</p>';
                break;
        }
        
        $message .= '</div>';
        
        return $message;
    }
}