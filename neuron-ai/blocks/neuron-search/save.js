/**
 * Neuron Search Block - Save Component.
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * Save component for the Neuron Search block.
 * 
 * @param {Object} props Block properties.
 * @return {WPElement} Block save element.
 */
export default function Save({ attributes }) {
    const { query, results } = attributes;
    const blockProps = useBlockProps.save({
        className: 'neuron-search-block',
    });
    
    return (
        <div { ...blockProps }>
            {query && (
                <div className="neuron-search-query">
                    <strong>{query}</strong>
                </div>
            )}
            
            {results && (
                <div 
                    className="neuron-search-results"
                    dangerouslySetInnerHTML={{ __html: results }}
                />
            )}
        </div>
    );
}