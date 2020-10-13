<?php

/**
 * The service container contract
 */
namespace Rmk\ServiceContainer;

use Rmk\Container\ContainerInterface;

/**
 * Interface ServiceContainerInterface
 *
 * @package Rmk\ServiceContainer
 */
interface ServiceContainerInterface extends ContainerInterface
{

    /**
     * Initialize the service container with the specific configuration array
     *
     * @param array $config The configuration array
     */
    public function init(array $config): void;

    /**
     * Adds a factory to the list
     *
     * @param mixed  $factory Service factory, either its name or callable
     * @param string $id      Service name
     */
    public function addFactory($factory, string $id): void;

    /**
     * Set a service as a singletone or not
     *
     * @param bool   $isSingletone Whether the service is or is not a singletone
     * @param string $id           The service name
     */
    public function setSingletone(bool $isSingletone, string $id): void;
}
