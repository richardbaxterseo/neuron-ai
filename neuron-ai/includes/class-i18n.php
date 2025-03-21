<?php
/**
 * Define the internationalization functionality.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
class I18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'neuron-ai',
            false,
            dirname(NEURON_AI_BASENAME) . '/languages/'
        );
    }
}