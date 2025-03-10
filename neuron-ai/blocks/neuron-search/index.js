/**
 * Neuron Search Block.
 *
 * A research block powered by AI to search for information on specific topics.
 */

// Import WordPress dependencies
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Internal dependencies
import Edit from './edit';
import Save from './save';

// Register the block
registerBlockType('neuron-ai/neuron-search', {
    /**
     * @see ./block.json
     */
    edit: Edit,
    save: Save,
    
    // Add an example for the block inserter
    example: {
        attributes: {
            query: __('Artificial Intelligence', 'neuron-ai'),
            results: __('<h2>Artificial Intelligence</h2><p>This is an example of AI-generated search results about artificial intelligence...</p>', 'neuron-ai'),
        },
    },
});