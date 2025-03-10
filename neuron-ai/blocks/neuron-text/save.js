/**
 * Neuron Text Block - Save Component.
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * Save component for the Neuron Text block.
 * 
 * @param {Object} props Block properties.
 * @return {WPElement} Block save element.
 */
export default function Save({ attributes }) {
    const { content } = attributes;
    const blockProps = useBlockProps.save({
        className: 'neuron-text-block',
    });
    
    return (
        <div { ...blockProps }>
            <RichText.Content
                tagName="div"
                className="neuron-text-content"
                value={ content }
            />
        </div>
    );
}