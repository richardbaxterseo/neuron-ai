/**
 * Neuron Search Block - Edit Component.
 */

// WordPress dependencies
import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    RichText,
    InspectorControls,
} from '@wordpress/block-editor';
import {
    PanelBody,
    PanelRow,
    SelectControl,
    ToggleControl,
    TextControl,
    Button,
    Spinner
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Edit component for the Neuron Search block.
 * 
 * @param {Object} props Block properties.
 * @return {WPElement} Block edit element.
 */
export default function Edit({ attributes, setAttributes }) {
    const { query, results, isSearching, searchOptions, provider } = attributes;
    const [searchError, setSearchError] = useState(null);
    
    const blockProps = useBlockProps({
        className: 'neuron-search-block',
    });
    
    // Handle search request
    const performSearch = async () => {
        if (!query.trim()) return;
        
        setAttributes({ isSearching: true });
        setSearchError(null);
        
        try {
            const response = await apiFetch({
                path: '/neuron-ai/v1/search',
                method: 'POST',
                data: {
                    query,
                    options: searchOptions,
                    provider: provider
                }
            });
            
            setAttributes({
                results: response.results,
                isSearching: false
            });
        } catch (error) {
            console.error('Search error:', error);
            setSearchError(error.message || 'An error occurred while searching.');
            setAttributes({ isSearching: false });
        }
    };
    
    // Handle clearing results
    const clearResults = () => {
        setAttributes({ results: '' });
    };
    
    return (
        <>
            <InspectorControls>
                <PanelBody
                    title={__('Search Options', 'neuron-ai')}
                    initialOpen={true}
                >
                    <SelectControl
                        label={__('Detail Level', 'neuron-ai')}
                        value={searchOptions.detail_level}
                        options={[
                            { label: __('Brief', 'neuron-ai'), value: 'brief' },
                            { label: __('Medium', 'neuron-ai'), value: 'medium' },
                            { label: __('Comprehensive', 'neuron-ai'), value: 'comprehensive' },
                        ]}
                        onChange={(detail_level) => setAttributes({
                            searchOptions: { ...searchOptions, detail_level }
                        })}
                    />
                    
                    <ToggleControl
                        label={__('Include Citations', 'neuron-ai')}
                        checked={searchOptions.include_citations}
                        onChange={(include_citations) => setAttributes({
                            searchOptions: { ...searchOptions, include_citations }
                        })}
                    />
                    
                    <SelectControl
                        label={__('Format', 'neuron-ai')}
                        value={searchOptions.format}
                        options={[
                            { label: __('Article', 'neuron-ai'), value: 'article' },
                            { label: __('FAQ', 'neuron-ai'), value: 'faq' },
                            { label: __('List', 'neuron-ai'), value: 'list' },
                            { label: __('Comparison', 'neuron-ai'), value: 'comparison' },
                        ]}
                        onChange={(format) => setAttributes({
                            searchOptions: { ...searchOptions, format }
                        })}
                    />
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
                        
                        <p className="components-base-control__help">
                            {__('Gemini is recommended for search operations.', 'neuron-ai')}
                        </p>
                    </PanelBody>
                )}
            </InspectorControls>
            
            <div {...blockProps}>
                <div className="neuron-search-query-container">
                    <TextControl
                        label={__('Search Query', 'neuron-ai')}
                        value={query}
                        onChange={(newQuery) => setAttributes({ query: newQuery })}
                        placeholder={__('What would you like to research?', 'neuron-ai')}
                    />
                    
                    <div className="neuron-search-actions">
                        <Button
                            isPrimary
                            onClick={performSearch}
                            disabled={isSearching || !query.trim()}
                        >
                            {isSearching ? (
                                <>
                                    <Spinner />
                                    {__('Searching...', 'neuron-ai')}
                                </>
                            ) : (
                                __('Search with AI', 'neuron-ai')
                            )}
                        </Button>
                        
                        {results && (
                            <Button
                                isSecondary
                                onClick={clearResults}
                                disabled={isSearching}
                            >
                                {__('Clear Results', 'neuron-ai')}
                            </Button>
                        )}
                    </div>
                </div>
                
                {searchError && (
                    <div className="neuron-ai-error">
                        <p>{searchError}</p>
                    </div>
                )}
                
                {results ? (
                    <div className="neuron-search-results-container">
                        <div className="neuron-search-results">
                            <RichText
                                tagName="div"
                                value={results}
                                onChange={(newResults) => setAttributes({ results: newResults })}
                                placeholder={__('Search results will appear here...', 'neuron-ai')}
                            />
                        </div>
                    </div>
                ) : (
                    <div className="neuron-search-placeholder">
                        <p>
                            {isSearching ? (
                                <>
                                    <Spinner />
                                    {__('Searching...', 'neuron-ai')}
                                </>
                            ) : (
                                __('Enter a query and click "Search with AI" to get AI-powered research results.', 'neuron-ai')
                            )}
                        </p>
                    </div>
                )}
            </div>
        </>
    );
}