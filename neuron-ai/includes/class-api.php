<?php
/**
 * The API class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

use NeuronAI\Traits\ContainerAware;
use NeuronAI\Providers\ProviderConstants;
use NeuronAI\Providers\ProviderException;
use NeuronAI\Providers\ProviderErrorHandler;


/**
 * API class.
 *
 * Registers and handles REST API endpoints.
 */
class API {

    use ContainerAware;

    /**
     * The endpoint namespace.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $namespace    The endpoint namespace.
     */
    private $namespace = 'neuron-ai/v1';

    /**
     * The provider factory.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Providers\ProviderFactory    $provider_factory    The provider factory.
     */
    private $provider_factory;

    /**
     * The error handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Providers\ProviderErrorHandler    $error_handler    The error handler.
     */
    private $error_handler;

    /**
     * The logger.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Logger    $logger    The logger.
     */
    private $logger;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Will be initialized when container is set
    }

    /**
     * Initialize services after container is set.
     *
     * @since    1.0.0
     */
    private function initServices() {
        if ($this->container) {
            if (!$this->provider_factory) {
                $this->provider_factory = $this->getService('provider_factory');
            }
            
            if (!$this->error_handler) {
                $this->error_handler = $this->getService('provider_error_handler');
            }
            
            if (!$this->logger) {
                $this->logger = $this->getService('logger');
            }
        }
    }

    /**
     * Register the REST API routes.
     *
     * @since    1.0.0
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/enhance',
            [
                'methods' => 'POST',
                'callback' => [$this, 'enhance_content'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'content' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                    'page_context' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'default' => '',
                    ],
                    'options' => [
                        'required' => false,
                        'type' => 'object',
                        'default' => [],
                    ],
                    'provider' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '',
                    ],
                ],
            ]
        );
        
        register_rest_route(
            $this->namespace,
            '/search',
            [
                'methods' => 'POST',
                'callback' => [$this, 'search_content'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'query' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'options' => [
                        'required' => false,
                        'type' => 'object',
                        'default' => [],
                    ],
                    'provider' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '',
                    ],
                ],
            ]
        );
        
        register_rest_route(
            $this->namespace,
            '/chat',
            [
                'methods' => 'POST',
                'callback' => [$this, 'chat_conversation'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => [
                    'conversations' => [
                        'required' => true,
                        'type' => 'array',
                    ],
                    'page_context' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'default' => '',
                    ],
                    'options' => [
                        'required' => false,
                        'type' => 'object',
                        'default' => [],
                    ],
                    'provider' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '',
                    ],
                ],
            ]
        );
        
        register_rest_route(
            $this->namespace,
            '/providers/status',
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_providers_status'],
                'permission_callback' => [$this, 'check_permission'],
            ]
        );
    }

    /**
     * Check if the current user has permission to access the endpoints.
     *
     * @since    1.0.0
     * @return   bool    Whether the user has permission.
     */
    public function check_permission() {
        return current_user_can('edit_posts');
    }

    /**
     * Enhance content endpoint handler.
     *
     * @since    1.0.0
     * @param    \WP_REST_Request    $request    The request object.
     * @return   \WP_REST_Response|\WP_Error     The response or error.
     */
    public function enhance_content($request) {
        $this->initServices();
        
        $params = $request->get_params();
        $content = $params['content'];
        $page_context = $params['page_context'] ?? '';
        $options = $params['options'] ?? [];
        $provider_name = $params['provider'] ?? '';
        
        try {
            // Get the appropriate provider
            $provider = $provider_name
                ? $this->provider_factory->getProvider(
                    $provider_name,
                    get_option('neuron_ai_' . $provider_name . '_key', ''),
                    get_option('neuron_ai_' . $provider_name . '_model', '')
                )
                : $this->provider_factory->getProviderForCapability(
                    ProviderConstants::CAPABILITY_ENHANCE,
                    $provider_name
                );
            
            // Process the enhancement request
            $enhanced_content = $provider->enhance($content, $page_context, $options);
            
            return new \WP_REST_Response([
                'content' => $enhanced_content,
                'provider' => $provider->getName(),
                'model' => $provider->getAvailableModels()[$provider->model]['name'] ?? $provider->model,
            ], 200);
        } catch (ProviderException $e) {
            return $this->error_handler->handleRestException($e);
        } catch (\Exception $e) {
            // Log unexpected errors
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage());
            }
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('An unexpected error occurred.', 'neuron-ai'),
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search content endpoint handler.
     *
     * @since    1.0.0
     * @param    \WP_REST_Request    $request    The request object.
     * @return   \WP_REST_Response|\WP_Error     The response or error.
     */
    public function search_content($request) {
        $this->initServices();
        
        $params = $request->get_params();
        $query = $params['query'];
        $options = $params['options'] ?? [];
        $provider_name = $params['provider'] ?? '';
        
        try {
            // Get the appropriate provider, preferring Gemini for search
            $provider = $provider_name
                ? $this->provider_factory->getProvider(
                    $provider_name,
                    get_option('neuron_ai_' . $provider_name . '_key', ''),
                    get_option('neuron_ai_' . $provider_name . '_model', '')
                )
                : $this->provider_factory->getProviderForCapability(
                    ProviderConstants::CAPABILITY_SEARCH,
                    $provider_name ?: ProviderConstants::PROVIDER_GEMINI
                );
            
            // Process the search request
            $search_results = $provider->search($query, $options);
            
            // Format results with wpautop
            $formatted_results = wpautop($search_results);
            
            return new \WP_REST_Response([
                'results' => $formatted_results,
                'provider' => $provider->getName(),
                'model' => $provider->getAvailableModels()[$provider->model]['name'] ?? $provider->model,
            ], 200);
        } catch (ProviderException $e) {
            return $this->error_handler->handleRestException($e);
        } catch (\Exception $e) {
            // Log unexpected errors
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage());
            }
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('An unexpected error occurred.', 'neuron-ai'),
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chat conversation endpoint handler.
     *
     * @since    1.0.0
     * @param    \WP_REST_Request    $request    The request object.
     * @return   \WP_REST_Response|\WP_Error     The response or error.
     */
    public function chat_conversation($request) {
        $this->initServices();
        
        $params = $request->get_params();
        $conversations = $params['conversations'];
        $page_context = $params['page_context'] ?? '';
        $options = $params['options'] ?? [];
        $provider_name = $params['provider'] ?? '';
        
        try {
            // Get the appropriate provider
            $provider = $provider_name
                ? $this->provider_factory->getProvider(
                    $provider_name,
                    get_option('neuron_ai_' . $provider_name . '_key', ''),
                    get_option('neuron_ai_' . $provider_name . '_model', '')
                )
                : $this->provider_factory->getProviderForCapability(
                    ProviderConstants::CAPABILITY_CHAT,
                    $provider_name
                );
            
            // Process the chat request
            $response = $provider->chat($conversations, $page_context, $options);
            
            return new \WP_REST_Response([
                'content' => $response,
                'provider' => $provider->getName(),
                'model' => $provider->getAvailableModels()[$provider->model]['name'] ?? $provider->model,
            ], 200);
        } catch (ProviderException $e) {
            return $this->error_handler->handleRestException($e);
        } catch (\Exception $e) {
            // Log unexpected errors
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage());
            }
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('An unexpected error occurred.', 'neuron-ai'),
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get providers status endpoint handler.
     *
     * @since    1.0.0
     * @param    \WP_REST_Request    $request    The request object.
     * @return   \WP_REST_Response|\WP_Error     The response or error.
     */
    public function get_providers_status($request) {
        $this->initServices();
        
        try {
            $status = $this->provider_factory->getProvidersStatus();
            
            return new \WP_REST_Response([
                'status' => $status,
            ], 200);
        } catch (\Exception $e) {
            // Log unexpected errors
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage());
            }
            
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('An unexpected error occurred.', 'neuron-ai'),
                'detail' => $e->getMessage()
            ], 500);
        }
    }
}