<?php
/**
 * The provider interface.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Providers
 */

namespace NeuronAI\Providers;

/**
 * Provider interface.
 *
 * This interface defines the contract for all AI service providers.
 */
interface Provider {

    /**
     * Enhance text content.
     *
     * @since    1.0.0
     * @param    string    $content       The content to enhance.
     * @param    string    $page_context  The surrounding page content for context.
     * @param    array     $options       Enhancement options.
     * @return   string                   The enhanced content.
     * @throws   \Exception               If enhancement fails.
     */
    public function enhance($content, $page_context = '', $options = []);

    /**
     * Search for information.
     *
     * @since    1.0.0
     * @param    string    $query     The search query.
     * @param    array     $options   Search options.
     * @return   string               The search results.
     * @throws   \Exception           If search fails.
     */
    public function search($query, $options = []);

    /**
     * Process a chat conversation.
     *
     * @since    1.0.0
     * @param    array     $conversations  Array of previous messages in the conversation.
     * @param    string    $page_context   The surrounding page content for context.
     * @param    array     $options        Chat options.
     * @return   string                    The AI response.
     * @throws   \Exception                If chat processing fails.
     */
    public function chat($conversations, $page_context = '', $options = []);

    /**
     * Test the API connection.
     *
     * @since    1.0.0
     * @return   array    Result with success status and message.
     */
    public function testConnection();

    /**
     * Get the provider name.
     *
     * @since    1.0.0
     * @return   string    The provider name.
     */
    public function getName();

    /**
     * Get the provider capabilities.
     *
     * @since    1.0.0
     * @return   array    The provider capabilities.
     */
    public function getCapabilities();

    /**
     * Check if the provider has a specific capability.
     *
     * @since    1.0.0
     * @param    string    $capability    The capability to check.
     * @return   bool                     Whether the provider has the capability.
     */
    public function hasCapability($capability);

    /**
     * Set the API key.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key.
     * @return   self                  The provider instance.
     */
    public function setApiKey($api_key);

    /**
     * Set the model to use.
     *
     * @since    1.0.0
     * @param    string    $model    The model identifier.
     * @return   self                The provider instance.
     */
    public function setModel($model);

    /**
     * Get available models.
     *
     * @since    1.0.0
     * @return   array    Array of available models.
     */
    public function getAvailableModels();
}