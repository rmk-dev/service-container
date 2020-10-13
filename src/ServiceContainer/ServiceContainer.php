<?php

/**
 * The service container class
 */
namespace Rmk\ServiceContainer;

use Rmk\ServiceContainer\Exception\ServiceNotFoundException;
use Rmk\ServiceContainer\Exception\InvalidFactoryException;
use Rmk\Container\Container;

/**
 * Class ServiceContainer
 *
 * @package Rmk\ServiceContainer
 */
class ServiceContainer extends Container implements ServiceContainerInterface
{

    const CONFIG_KEY = 'config';

    const CONFIG_SERVICES_KEY = 'services';

    const CONFIG_FACTORIES_KEY = 'factories';

    const CONFIG_SINGLETONES_KEY = 'singletones';

    /**
     * Container with factories
     *
     * @var Container
     */
    protected $factories;

    /**
     * Container with singletone definictions
     *
     * @var Container
     */
    protected $singletones;

    /**
     * ServiceContainer constructor.
     *
     * @param array $values [Optional] Initial values with services
     */
    public function __construct(array $values = [])
    {
        parent::__construct($values);
        $this->factories = new Container([
            InjectionFactory::class => new InjectionFactory()
        ]);
        $this->singletones = new Container([InjectionFactory::class => false]);
    }

    /**
     * Initialize the service container with the specific configuration array
     *
     * @param array $config The configuration array
     */
    public function init(array $config): void
    {
        $this->add(new Container($config), self::CONFIG_KEY);
        if (array_key_exists(self::CONFIG_SERVICES_KEY, $config)) {            
            $this->initFactories($config[self::CONFIG_SERVICES_KEY]);
            $this->initSingletones($config[self::CONFIG_SERVICES_KEY]);
        }
    }

    /**
     * Initialize factory list
     *
     * @param array $services List with services configurations
     */
    protected function initFactories(array $services): void
    {
        if (array_key_exists(self::CONFIG_FACTORIES_KEY, $services)) {
            $factories = $services[self::CONFIG_FACTORIES_KEY];
            foreach ($factories as $id => $factory) {
                $this->addFactory($factory, $id);
            }
        }
    }

    /**
     * Initialize the list with singletones definitions
     *
     * @param array $services List with the singletone definitions
     */
    protected function initSingletones(array $services): void
    {
        if (array_key_exists(self::CONFIG_SINGLETONES_KEY, $services)) {
            $singletones = $services[self::CONFIG_SINGLETONES_KEY];
            foreach ($singletones as $id => $isSingletone) {
                $this->setSingletone((bool) $isSingletone, $id);
            }
        }
    }

    /**
     * Returns the requested service if it exists
     *
     * @param string $id The service name
     *
     * @return mixed The service
     */
    public function get($id)
    {
        if ($this->singletones->has($id) && !$this->singletones->get($id)) {
            return $this->createService($id);
        }
        if (!parent::has($id)) {
            $this->add($this->createService($id), $id);
        }
        return parent::get($id);
    }

    /**
     * Checks whether the container contains the service with specific name
     *
     * @param string $id The service name
     *
     * @return bool True if exists, otherwise false
     */
    public function has($id)
    {
        return parent::has($id) || $this->factories->has($id);
    }

    /**
     * Creates a service by its name and configured factory
     *
     * @param string $id The service name
     *
     * @return mixed The created service
     */
    protected function createService(string $id)
    {
        if (!$this->factories->has($id)) {
            throw new ServiceNotFoundException('Service '.$id.' not found', 1, $id);
        }
        $factory = $this->factories->get($id);
        if (is_callable($factory)) {
            $service = $factory($this, $id);
        } else {
            throw new InvalidFactoryException(
                'Invalid factory for service '.$id, 
                1, 
                $id, 
                $factory
            );
        }
        return $service;
    }

    /**
     * Adds a factory to the list
     *
     * @param mixed  $factory Service factory, either its name or callable
     * @param string $id      Service name
     */
    public function addFactory($factory, string $id): void
    {
        if (is_string($factory) &&
            $factory !== InjectionFactory::class &&
            class_exists($factory)
        ) {
            $injectionFactory = $this->get(InjectionFactory::class);
            $this->factories->add($injectionFactory($this, $factory), $id);
        } elseif ($factory === InjectionFactory::class) {
            $this->factories->add($this->get(InjectionFactory::class), $id);
        } else {
            $this->factories->add($factory, $id);
        }
    }

    /**
     * Set a service as a singletone or not
     *
     * @param bool   $isSingletone Whether the service is or is not a singletone
     * @param string $id           The service name
     */
    public function setSingletone(bool $isSingletone, string $id): void
    {
        $this->singletones->add($isSingletone, $id);
    }

    /**
     * Return the container with all the factories
     *
     * @return Container The factories container
     */
    public function getFactories(): Container
    {
        return $this->factories;
    }

    /**
     * Returns the container with the singletones definitions
     *
     * @return Container The singletone definitions container
     */
    public function getSingletones(): Container
    {
        return $this->singletones;
    }
}
