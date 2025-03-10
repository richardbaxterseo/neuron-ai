/**
 * Neuron Text Block - Edit Component.
 */

// WordPress dependencies
import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    RichText,
    BlockControls,
    InspectorControls,
} from '@wordpress/block-editor';
import {
    ToolbarGroup,
    ToolbarButton,
    PanelBody,
    PanelRow,
    SelectControl,
    TextareaControl,
    Button,
    Modal,
    Spinner
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { select } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { magic } from '@wordpress/icons';

/**
 * Edit component for the Neuron Text block.
 * 
 * @param {Object} props Block properties.
 * @return {WPElement} Block edit element.
 */
export default function Edit({ attributes, setAttributes }) {
    const { content, isEnhancing, enhancementOptions, provider, conversations } = attributes;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [currentPrompt, setCurrentPrompt] = useState('');
    const [enhancementError, setEnhancementError] = useState(null);
    const [isConversationMode, setIsConversationMode] = useState(false);

    const blockProps = useBlockProps({
        className: 'neuron-text-block',
    });

    // Helper to get page context from other blocks
    const getPageContext = () => {
        const blocks = select('core/block-editor').getBlocks();
        const currentBlockClientId = select('core/block-editor').getSelectedBlockClientId();
        
        const blockTexts = blocks
            .filter(block => block.clientId !== currentBlockClientId) // Skip current block
            .map(block => {
                // Try to extract text from block
                if (block.attributes.content) {
                    return block.attributes.content;
                }
                return '';
            })
            .filter(text => text); // Remove empty strings
        
        return blockTexts.join('\n\n');
    };

    // Handle enhancement request
    const enhanceContent = async () => {
        if (!content) return;
        
        setAttributes({ isEnhancing: true });
        setEnhancementError(null);
        
        try {
            const response = await apiFetch({
                path: '/neuron-ai/v1/enhance',
                method: 'POST',
                data: {
                    content,
                    page_context: getPageContext(),
                    options: enhancementOptions,
                    provider: provider
                }
            });
            
            setAttributes({
                content: response.content,
                isEnhancing: false
            });
        } catch (error) {
            console.error('Enhancement error:', error);
            setEnhancementError(error.message || 'An error occurred while enhancing the content.');
            setAttributes({ isEnhancing: false });
        }
    };

    // Handle conversation mode
    const submitPrompt = async () => {
        if (!currentPrompt.trim()) return;
        
        // Add user prompt to conversation
        const updatedConversations = [
            ...conversations,
            {
                role: 'user',
                content: currentPrompt
            }
        ];
        
        setAttributes({ 
            conversations: updatedConversations,
            isEnhancing: true 
        });
        
        setEnhancementError(null);
        
        try {
            const response = await apiFetch({
                path: '/neuron-ai/v1/chat',
                method: 'POST',
                data: {
                    conversations: updatedConversations,
                    page_context: getPageContext(),
                    options: enhancementOptions,
                    provider: provider
                }
            });
            
            // Add AI response to conversation
            updatedConversations.push({
                role: 'assistant',
                content: response.content
            });
            
            setAttributes({
                conversations: updatedConversations,
                isEnhancing: false
            });
            
            setCurrentPrompt('');
        } catch (error) {
            console.error('Chat error:', error);
            setEnhancementError(error.message || 'An error occurred during the conversation.');
            setAttributes({ isEnhancing: false });
        }
    };

    // Apply AI response to content
    const applyResponse = (response) => {
        setAttributes({
            content: response,
            isConversationMode: false
        });
    };

    // Reset conversation
    const resetConversation = () => {
        setAttributes({
            conversations: [],
            isConversationMode: false
        });
    };

    // Toggle conversation mode
    const toggleConversationMode = () => {
        setIsConversationMode(!isConversationMode);
        
        if (!isConversationMode && conversations.length === 0) {
            // Starting a new conversation, add current content as context
            setAttributes({
                conversations: [
                    {
                        role: 'system',
                        content: `The user has the following content that they may want to modify: ${content}`
                    }
                ]
            });
        }
    };

    // Enhancement settings modal
    const enhancementModal = isModalOpen && (
        <Modal
            title={__('AI Enhancement Settings', 'neuron-ai')}
            onRequestClose={() => setIsModalOpen(false)}
            className="neuron-ai-modal"
        >
            <SelectControl
                label={__('Tone', 'neuron-ai')}
                value={enhancementOptions.tone}
                options={[
                    { label: __('Professional', 'neuron-ai'), value: 'professional' },
                    { label: __('Casual', 'neuron-ai'), value: 'casual' },
                    { label: __('Academic', 'neuron-ai'), value: 'academic' },
                    { label: __('Creative', 'neuron-ai'), value: 'creative' },
                ]}
                onChange={(tone) => setAttributes({
                    enhancementOptions: { ...enhancementOptions, tone }
                })}
            />
            
            <SelectControl
                label={__('Reading Level', 'neuron-ai')}
                value={enhancementOptions.reading_level}
                options={[
                    { label: __('Universal', 'neuron-ai'), value: 'universal' },
                    { label: __('Simple', 'neuron-ai'), value: 'simple' },
                    { label: __('Intermediate', 'neuron-ai'), value: 'intermediate' },
                    { label: __('Advanced', 'neuron-ai'), value: 'advanced' },
                ]}
                onChange={(reading_level) => setAttributes({
                    enhancementOptions: { ...enhancementOptions, reading_level }
                })}
            />
            
            <TextareaControl
                label={__('Special Instructions', 'neuron-ai')}
                help={__('Add any specific enhancement instructions.', 'neuron-ai')}
                value={enhancementOptions.instructions}
                onChange={(instructions) => setAttributes({
                    enhancementOptions: { ...enhancementOptions, instructions }
                })}
            />
            
            <div className="neuron-ai-modal-actions">
                <Button
                    isPrimary
                    onClick={() => setIsModalOpen(false)}
                >
                    {__('Save Settings', 'neuron-ai')}
                </Button>
            </div>
        </Modal>
    );

    // Conversation mode UI
    const conversationUI = isConversationMode && (
        <div className="neuron-conversation-mode">
            {conversations.filter(msg => msg.role !== 'system').map((msg, index) => (
                <div key={index} className={`conversation-message ${msg.role}`}>
                    <div className="message-header">
                        {msg.role === 'user' ? __('You', 'neuron-ai') : __('AI Assistant', 'neuron-ai')}
                    </div>
                    <div className="message-content">
                        {msg.role === 'assistant' ? (
                            <>
                                <div dangerouslySetInnerHTML={{ __html: msg.content }} />
                                <Button
                                    isSecondary
                                    isSmall
                                    onClick={() => applyResponse(msg.content)}
                                    className="apply-response-button"
                                >
                                    {__('Apply to Content', 'neuron-ai')}
                                </Button>
                            </>
                        ) : (
                            <p>{msg.content}</p>
                        )}
                    </div>
                </div>
            ))}
            
            <div className="prompt-input">
                <TextareaControl
                    placeholder={__('Ask the AI assistant...', 'neuron-ai')}
                    value={currentPrompt}
                    onChange={setCurrentPrompt}
                    disabled={isEnhancing}
                />
                <div className="prompt-actions">
                    <Button 
                        isPrimary 
                        onClick={submitPrompt}
                        disabled={isEnhancing || !currentPrompt.trim()}
                    >
                        {isEnhancing ? (
                            <>
                                <Spinner />
                                {__('Processing...', 'neuron-ai')}
                            </>
                        ) : (
                            __('Submit', 'neuron-ai')
                        )}
                    </Button>
                    <Button 
                        isSecondary
                        onClick={resetConversation}
                        disabled={isEnhancing || conversations.length === 0}
                    >
                        {__('Reset Conversation', 'neuron-ai')}
                    </Button>
                </div>
            </div>
            
            {enhancementError && (
                <div className="neuron-ai-error">
                    <p>{enhancementError}</p>
                </div>
            )}
        </div>
    );

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon={magic}
                        label={__('Enhance with AI', 'neuron-ai')}
                        onClick={enhanceContent}
                        disabled={isEnhancing || !content}
                    />
                </ToolbarGroup>
            </BlockControls>
            
            <InspectorControls>
                <PanelBody
                    title={__('AI Enhancement Options', 'neuron-ai')}
                    initialOpen={true}
                >
                    <PanelRow>
                        <Button
                            isSecondary
                            onClick={() => setIsModalOpen(true)}
                        >
                            {__('Configure Enhancement Settings', 'neuron-ai')}
                        </Button>
                    </PanelRow>
                    
                    <PanelRow>
                        <Button
                            isPrimary
                            onClick={enhanceContent}
                            disabled={isEnhancing || !content}
                        >
                            {isEnhancing ? __('Enhancing...', 'neuron-ai') : __('Enhance with AI', 'neuron-ai')}
                        </Button>
                    </PanelRow>
                    
                    <PanelRow>
                        <Button
                            isSecondary
                            onClick={toggleConversationMode}
                        >
                            {isConversationMode ? __('Exit Conversation Mode', 'neuron-ai') : __('Enter Conversation Mode', 'neuron-ai')}
                        </Button>
                    </PanelRow>
                </PanelBody>
                
                {neuronAI.providers && Object.keys(neuronAI.providers).length > 0 && (
                    <PanelBody
                        title={__('AI Provider', 'neuron-ai')}
                        initialOpen={false}
                    >
                        <SelectControl
                            label={__('Select Provider', 'neuron-ai')}
                            value={provider}
                            options={[
                                { label: __('Default (Recommended)', 'neuron-ai'), value: '' },
                                ...Object.entries(neuronAI.providers).map(([id, data]) => ({
                                    label: data.name,
                                    value: id
                                }))
                            ]}
                            onChange={(value) => setAttributes({ provider: value })}
                        />
                    </PanelBody>
                )}
            </InspectorControls>
            
            {enhancementModal}
            
            <div {...blockProps}>
                {isEnhancing && !isConversationMode && (
                    <div className="neuron-ai-loading">
                        <Spinner />
                        <p>{__('Enhancing content with AI...', 'neuron-ai')}</p>
                    </div>
                )}
                
                {enhancementError && !isConversationMode && (
                    <div className="neuron-ai-error">
                        <p>{enhancementError}</p>
                    </div>
                )}
                
                {isConversationMode ? (
                    conversationUI
                ) : (
                    <RichText
                        tagName="div"
                        className="neuron-text-content"
                        value={content}
                        onChange={(newContent) => setAttributes({ content: newContent })}
                        placeholder={__('Write content here or enter conversation mode to ask the AI assistant...', 'neuron-ai')}
                    />
                )}
            </div>
        </>
    );
}