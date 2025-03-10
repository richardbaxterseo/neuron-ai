<?php
/**
 * The Claude provider implementation.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Providers
 */

namespace NeuronAI\Providers;

use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\History\InMemoryChatHistory;
use NeuronAI\Agent;

/**
 * Claude Provider class.
 *
 * Implements the provider interface for Anthropic Claude.
 */
class ClaudeProvider extends AbstractProvider {

    /**
     * The Neuron AI agent instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \NeuronAI\Agent    $agent    The agent instance.
     */
    private $agent;

    /**
     * Initialize the Neuron AI agent.
     *
     * @since    1.0.0
     * @return   \NeuronAI\Agent    The configured agent.
     * @throws   \NeuronAI\Providers\ProviderException    If initialization fails.
     */
    private function initAgent() {
        if (!$this->api_key) {
            throw new ProviderException(
                'Claude API key not provided.',
                ProviderException::ERROR_MISSING_API_KEY
            );
        }

        try {
            $agent = new Agent();
            
            // Set up the Anthropic provider with the Neuron AI framework
            // Note: This class is provided by the Neuron AI PHP framework dependency
            $anthropic_provider = new \NeuronAI\Providers\Anthropic\Anthropic(
                $this->api_key,
                $this->model
            );
            
            $agent->setProvider($anthropic_provider);
            
            return $agent;
        } catch (\Exception $e) {
            throw new ProviderException(
                'Failed to initialize Claude agent: ' . $e->getMessage(),
                ProviderException::ERROR_INITIALIZATION_FAILED,
                $e
            );
        }
    }

    /**
     * Enhance text content.
     *
     * @since    1.0.0
     * @param    string    $content       The content to enhance.
     * @param    string    $page_context  The surrounding page content for context.
     * @param    array     $options       Enhancement options.
     * @return   string                   The enhanced content.
     * @throws   \NeuronAI\Providers\ProviderException    If enhancement fails.
     */
    public function enhance($content, $page_context = '', $options = []) {
        $this->initServices();

        if (empty($content)) {
            throw new ProviderException(
                'Content cannot be empty for enhancement.',
                ProviderException::ERROR_INVALID_REQUEST
            );
        }

        try {
            // Log the request
            if ($this->logger) {
                $this->logger->log_request('enhance', [
                    'provider' => ProviderConstants::PROVIDER_CLAUDE,
                    'prompt_length' => strlen($content),
                    'context_length' => strlen($page_context)
                ]);
            }

            $agent = $this->initAgent();
            
            // Prepare instructions for the enhancement
            $instructions = $this->build_enhancement_instructions($options);
            $agent->setInstructions($instructions);
            
            // Create the prompt with content and context
            $prompt = $this->build_enhancement_prompt($content, $page_context, $options);
            
            // Process the request
            $response = $agent->chat(new UserMessage($prompt));
            
            $result = $response->getContent();
            
            // Log the successful response
            if ($this->logger) {
                $this->logger->log_response(200, [
                    'response_length' => strlen($result)
                ]);
            }
            
            return $result;
        } catch (ProviderException $e) {
            // Re-throw provider exceptions for consistent handling
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage(), $e->getTechnicalDetails());
            }
            throw $e;
        } catch (\Exception $e) {
            // Log the error
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage());
            }
            
            // Determine specific error type based on exception message or code
            $error_code = ProviderException::ERROR_API_REQUEST_FAILED;
            $error_data = [];
            
            // Check for typical API error patterns
            if (strpos($e->getMessage(), 'rate limit') !== false || $e->getCode() == 429) {
                $error_code = ProviderException::ERROR_RATE_LIMIT_EXCEEDED;
            } elseif (strpos($e->getMessage(), 'invalid_api_key') !== false || 
                     strpos($e->getMessage(), 'authentication') !== false) {
                $error_code = ProviderException::ERROR_MISSING_API_KEY;
            } elseif (strpos($e->getMessage(), 'model') !== false && 
                     (strpos($e->getMessage(), 'not found') !== false || 
                      strpos($e->getMessage(), 'invalid') !== false)) {
                $error_code = ProviderException::ERROR_INVALID_MODEL;
            } elseif (strpos($e->getMessage(), 'content policy') !== false ||
                     strpos($e->getMessage(), 'content_policy') !== false) {
                $error_code = ProviderException::ERROR_API_RESPONSE_ERROR;
                $error_data = ['policy_violation' => true];
            }
            
            throw new ProviderException(
                'Enhancement failed: ' . $e->getMessage(),
                $error_code,
                $e,
                $error_data
            );
        }
    }

    /**
     * Search for information.
     *
     * @since    1.0.0
     * @param    string    $query     The search query.
     * @param    array     $options   Search options.
     * @return   string               The search results.
     * @throws   \NeuronAI\Providers\ProviderException    If search fails.
     */
    public function search($query, $options = []) {
        $this->initServices();

        if (empty($query)) {
            throw new ProviderException(
                'Search query cannot be empty.',
                ProviderException::ERROR_INVALID_REQUEST
            );
        }

        try {
            // Log the request
            if ($this->logger) {
                $this->logger->log_request('search', [
                    'provider' => ProviderConstants::PROVIDER_CLAUDE,
                    'query' => $query
                ]);
            }

            $agent = $this->initAgent();
            
            // Prepare instructions for the search
            $instructions = $this->build_search_instructions($options);
            $agent->setInstructions($instructions);
            
            // Create the search prompt
            $prompt = $this->build_search_prompt($query, $options);
            
            // Process the request
            $response = $agent->chat(new UserMessage($prompt));
            
            $result = $response->getContent();
            
            // Log the successful response
            if ($this->logger) {
                $this->logger->log_response(200, [
                    'response_length' => strlen($result)
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            // Log the error
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage());
            }
            
            throw new ProviderException(
                'Search failed: ' . $e->getMessage(),
                ProviderException::ERROR_API_REQUEST_FAILED,
                $e
            );
        }
    }

    /**
     * Process a chat conversation.
     *
     * @since    1.0.0
     * @param    array     $conversations  Array of previous messages in the conversation.
     * @param    string    $page_context   The surrounding page content for context.
     * @param    array     $options        Chat options.
     * @return   string                    The AI response.
     * @throws   \NeuronAI\Providers\ProviderException    If chat processing fails.
     */
    public function chat($conversations, $page_context = '', $options = []) {
        $this->initServices();

        if (empty($conversations)) {
            throw new ProviderException(
                'Conversation history cannot be empty.',
                ProviderException::ERROR_INVALID_REQUEST
            );
        }

        try {
            // Log the request
            if ($this->logger) {
                $last_message = end($conversations);
                $this->logger->log_request('chat', [
                    'provider' => ProviderConstants::PROVIDER_CLAUDE,
                    'prompt_length' => strlen($last_message['content'] ?? ''),
                    'context_length' => strlen($page_context)
                ]);
            }

            $agent = $this->initAgent();
            
            // Set up chat history
            $chatHistory = new InMemoryChatHistory();
            
            foreach ($conversations as $message) {
                if ($message['role'] === 'user') {
                    $chatHistory->addMessage(new UserMessage($message['content']));
                } else if ($message['role'] === 'assistant') {
                    $chatHistory->addMessage(new AssistantMessage($message['content']));
                }
            }
            
            $agent->withChatHistory($chatHistory);
            
            // Prepare instructions for the chat
            $instructions = $this->build_chat_instructions($page_context, $options);
            $agent->setInstructions($instructions);
            
            // Get the last message from the user
            $lastMessage = end($conversations);
            
            // Process the request
            $response = $agent->chat(new UserMessage($lastMessage['content']));
            
            $result = $response->getContent();
            
            // Log the successful response
            if ($this->logger) {
                $this->logger->log_response(200, [
                    'response_length' => strlen($result)
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            // Log the error
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage());
            }
            
            throw new ProviderException(
                'Chat failed: ' . $e->getMessage(),
                ProviderException::ERROR_API_REQUEST_FAILED,
                $e
            );
        }
    }

    /**
     * Test the API connection.
     *
     * @since    1.0.0
     * @return   array    Result with success status and message.
     */
    public function testConnection() {
        $this->initServices();

        if (empty($this->api_key)) {
            return [
                'success' => false,
                'message' => __('API key is not configured.', 'neuron-ai')
            ];
        }

        try {
            $agent = $this->initAgent();
            
            // Send a simple test message
            $response = $agent->chat(new UserMessage('Hello, this is a connection test.'));
            
            return [
                'success' => true,
                'message' => __('Successfully connected to Claude API.', 'neuron-ai'),
                'model' => $this->model
            ];
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->log_error($e->getCode(), $e->getMessage());
            }
            
            return [
                'success' => false,
                'message' => sprintf(
                    __('Connection test failed: %s', 'neuron-ai'),
                    $e->getMessage()
                )
            ];
        }
    }

    /**
     * Get the provider name.
     *
     * @since    1.0.0
     * @return   string    The provider name.
     */
    public function getName() {
        return ProviderConstants::PROVIDER_CLAUDE;
    }

    /**
     * Get the provider capabilities.
     *
     * @since    1.0.0
     * @return   array    The provider capabilities.
     */
    public function getCapabilities() {
        $model_caps = ProviderConstants::getModelCapabilities(
            ProviderConstants::PROVIDER_CLAUDE,
            $this->model
        );
        
        return $model_caps;
    }

    /**
     * Get available models.
     *
     * @since    1.0.0
     * @return   array    Array of available models.
     */
    public function getAvailableModels() {
        return ProviderConstants::getClaudeModels();
    }

    /**
     * Build enhancement instructions based on options.
     *
     * @since    1.0.0
     * @param    array     $options    Enhancement options.
     * @return   string                Enhancement instructions.
     */
    private function build_enhancement_instructions($options) {
        $tone = isset($options['tone']) ? $options['tone'] : 'professional';
        $reading_level = isset($options['reading_level']) ? $options['reading_level'] : 'universal';
        
        $instructions = "You are an AI content enhancement assistant. ";
        $instructions .= "Your task is to improve and refine the content while maintaining its core message and intent. ";
        
        // Add tone instructions
        switch ($tone) {
            case 'professional':
                $instructions .= "Use a professional, business-appropriate tone that is clear and concise. ";
                break;
            case 'casual':
                $instructions .= "Use a casual, conversational tone that is friendly and approachable. ";
                break;
            case 'academic':
                $instructions .= "Use an academic tone with formal language and thorough explanations. ";
                break;
            case 'creative':
                $instructions .= "Use a creative, engaging tone with vivid language and imagery. ";
                break;
            default:
                $instructions .= "Maintain a balanced, professional tone. ";
        }
        
        // Add reading level instructions
        switch ($reading_level) {
            case 'simple':
                $instructions .= "Write at approximately a 6th-8th grade reading level, using simple vocabulary and sentence structure. ";
                break;
            case 'intermediate':
                $instructions .= "Write at approximately a high school reading level, balancing clarity with some complexity. ";
                break;
            case 'advanced':
                $instructions .= "Write at a college/university level, using sophisticated vocabulary and complex sentence structures when appropriate. ";
                break;
            case 'universal':
            default:
                $instructions .= "Write at a universal reading level that is accessible to most adults while remaining engaging. ";
        }
        
        $instructions .= "Improve the content by enhancing clarity, fixing grammatical issues, improving flow, and making it more engaging. ";
        $instructions .= "Do not add new facts or change the meaning of the content. ";
        $instructions .= "Return the enhanced content only, without additional commentary.";
        
        return $instructions;
    }

    /**
     * Build enhancement prompt combining content and context.
     *
     * @since    1.0.0
     * @param    string    $content        The content to enhance.
     * @param    string    $page_context   The surrounding page context.
     * @param    array     $options        Enhancement options.
     * @return   string                    The complete prompt.
     */
    private function build_enhancement_prompt($content, $page_context, $options) {
        $prompt = "Please enhance the following content:\n\n";
        $prompt .= $content;
        
        if (!empty($page_context)) {
            $prompt .= "\n\nThis content appears in the following page context (for reference only, do not modify this):\n";
            $prompt .= $page_context;
            $prompt .= "\n\nEnsure the enhanced content maintains consistency with the overall page context.";
        }
        
        // Add any specific enhancement instructions from options
        if (!empty($options['instructions'])) {
            $prompt .= "\n\nSpecific instructions: " . $options['instructions'];
        }
        
        return $prompt;
    }

    /**
     * Build search instructions based on options.
     *
     * @since    1.0.0
     * @param    array     $options    Search options.
     * @return   string                Search instructions.
     */
    private function build_search_instructions($options) {
        $detail_level = isset($options['detail_level']) ? $options['detail_level'] : 'medium';
        $include_citations = isset($options['include_citations']) ? $options['include_citations'] : true;
        
        $instructions = "You are an AI research assistant. ";
        $instructions .= "Your task is to provide accurate, helpful information in response to queries. ";
        
        // Detail level instructions
        switch ($detail_level) {
            case 'brief':
                $instructions .= "Provide concise summaries with only key points and essential details. ";
                break;
            case 'medium':
                $instructions .= "Provide balanced overviews with main concepts and supporting details. ";
                break;
            case 'comprehensive':
                $instructions .= "Provide detailed explanations with thorough analysis and multiple perspectives. ";
                break;
            default:
                $instructions .= "Provide balanced information with appropriate level of detail. ";
        }
        
        // Citation instructions
        if ($include_citations) {
            $instructions .= "Include suggestions for credible sources that support your information. ";
            $instructions .= "Format the citations at the end of your response. ";
        }
        
        $instructions .= "Structure your response with clear headings, paragraphs, and bullet points when appropriate. ";
        $instructions .= "Format the response in clean HTML that can be directly inserted into WordPress content.";
        
        return $instructions;
    }

    /**
     * Build search prompt based on query and options.
     *
     * @since    1.0.0
     * @param    string    $query     The search query.
     * @param    array     $options   Search options.
     * @return   string               The complete prompt.
     */
    private function build_search_prompt($query, $options) {
        $prompt = "Research and provide information about: {$query}\n\n";
        
        // Additional context if provided
        if (!empty($options['context'])) {
            $prompt .= "Additional context: " . $options['context'] . "\n\n";
        }
        
        // Special formatting requests
        if (!empty($options['format'])) {
            $prompt .= "Please format the response as: " . $options['format'] . "\n\n";
        }
        
        return $prompt;
    }

    /**
     * Build chat instructions based on context and options.
     *
     * @since    1.0.0
     * @param    string    $page_context    The page context.
     * @param    array     $options         Chat options.
     * @return   string                     Chat instructions.
     */
    private function build_chat_instructions($page_context, $options) {
        $instructions = "You are a helpful content creation assistant. ";
        
        if (!empty($page_context)) {
            $instructions .= "Consider this context about the page the content appears in: {$page_context} ";
        }
        
        $instructions .= "Help the user create or refine content. ";
        
        // Add tone instructions if specified
        if (!empty($options['tone'])) {
            $instructions .= "Use a {$options['tone']} tone in your responses. ";
        }
        
        // Add style instructions if specified
        if (!empty($options['style'])) {
            $instructions .= "Write in a {$options['style']} style. ";
        }
        
        $instructions .= "Provide focused, helpful responses that directly address the user's needs. ";
        $instructions .= "When suggesting content, provide it in a format ready for WordPress.";
        
        return $instructions;
    }
}