<?php
/**
 * The service container class.
 *
 * @since      1.0.0
 * @package    NeuronAI
 */

namespace NeuronAI;

/**
 * The service container class.
 *
 * A simple dependency injection container.
 */
class Container {

    /**
     * The registered services.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $services    The registered services.
     */
    private $services = [];

    /**
     * The instantiated services.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $instances    The instantiated services.
     */
    private $instances = [];

    /**
     * Register a service with the container.
     *
     * @since    1.0.0
     * @param    string    $id          The service identifier.
     * @param    callable  $definition  The service definition.
     * @return   self                   The container instance.
     */
    public function register($id, callable $definition) {
        $this->services[$id] = $definition;
        return $this;
    }

    /**
     * Get a service from the container.
     *
     * @since    1.0.0
     * @param    string    $id          The service identifier.
     * @param    array     $params      Optional parameters for the service.
     * @return   mixed                  The service instance.
     * @throws   \Exception             If the service is not found.
     */
    public function get($id, array $params = []) {
        // Check if service exists
        if (!$this->has($id)) {
            throw new \Exception("Service with ID '$id' not found in container.");
        }

        // Check if service is already instantiated
        if (!isset($this->instances[$id])) {
            // Create the service
            $this->instances[$id] = $this->services[$id]($this, $params);
        }

        return $this->instances[$id];
    }

    /**
     * Check if a service exists in the container.
     *
     * @since    1.0.0
     * @param    string    $id    The service identifier.
     * @return   bool             Whether the service exists.
     */
    public function has($id) {
        return isset($this->services[$id]);
    }

    /**
     * Reset a service instance.
     *
     * @since    1.0.0
     * @param    string    $id    The service identifier.
     * @return   self             The container instance.
     */
    public function reset($id) {
        unset($this->instances[$id]);
        return $this;
    }

    /**
     * Reset all service instances.
     *
     * @since    1.0.0
     * @return   self    The container instance.
     */
    public function resetAll() {
        $this->instances = [];
        return $this;
    }
}