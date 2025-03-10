<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Deactivator {

    /**
     * Deactivate the plugin.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Perform cleanup tasks if needed
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}