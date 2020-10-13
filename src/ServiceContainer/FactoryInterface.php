<?php

namespace Rmk\ServiceContainer;

use Psr\Container\ContainerInterface;

interface FactoryInterface
{

    /**
     * Creates and returns the service
     *
     * @param ContainerInterface $serviceContainer The service container
     * @param string|null        $serviceName      The service name
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $serviceContainer, $serviceName = null);
}
