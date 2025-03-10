<?php
/**
 * Container Aware Trait.
 *
 * @since      1.0.0
 * @package    NeuronAI
 * @subpackage NeuronAI\Traits
 */

namespace NeuronAI\Traits;

use NeuronAI\Container;

/**
 * Container Aware Trait.
 *
 * Allows classes to access the service container.
 */
trait ContainerAware {

    /**
     * The service container instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      \NeuronAI\Container    $container    The service container.
     */
    protected $container;

    /**
     * Set the service container.
     *
     * @since    1.0.0
     * @param    \NeuronAI\Container    $container    The service container.
     * @return   self                                 The current instance.
     */
    public function setContainer(Container $container) {
        $this->container = $container;
        return $this;
    }

    /**
     * Get the service container.
     *
     * @since    1.0.0
     * @return   \NeuronAI\Container    The service container.
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * Get a service from the container.
     *
     * @since    1.0.0
     * @param    string    $id          The service identifier.
     * @param    array     $params      Optional parameters for the service.
     * @return   mixed                  The service instance.
     */
    protected function getService($id, array $params = []) {
        if (!$this->container) {
            throw new \RuntimeException('Container not set.');
        }

        return $this->container->get($id, $params);
    }

    /**
     * Check if a service exists in the container.
     *
     * @since    1.0.0
     * @param    string    $id    The service identifier.
     * @return   bool             Whether the service exists.
     */
    protected function hasService($id) {
        if (!$this->container) {
            throw new \RuntimeException('Container not set.');
        }

        return $this->container->has($id);
    }
}