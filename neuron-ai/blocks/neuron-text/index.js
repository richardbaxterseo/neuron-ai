/**
 * Neuron Text Block.
 *
 * A rich text block with AI enhancement capabilities.
 */

// Import WordPress dependencies
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Internal dependencies
import Edit from './edit';
import Save from './save';

// Register the block
registerBlockType('neuron-ai/neuron-text', {
    /**
     * @see ./block.json
     */
    edit: Edit,
    save: Save,
    
    // Add an example for the block inserter
    example: {
        attributes: {
            content: __('This is an example of the Neuron Text block. Write your content here and enhance it with AI.', 'neuron-ai'),
        },
    },
});