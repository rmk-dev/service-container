<?php

/**
 * The injection factory class
 */
namespace Rmk\ServiceContainer;

use ReflectionException;
use Rmk\ServiceContainer\Exception\InvalidServiceNameException;
use Rmk\ServiceContainer\Exception\ServiceNotCreatedException;
use Psr\Container\ContainerInterface;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionMethod;
use ReflectionClass;

/**
 * Class InjectionFactory
 *
 * @package Rmk\ServiceContainer
 */
class InjectionFactory implements FactoryInterface
{

    /**
     * The service container
     *
     * @var ContainerInterface
     */
    protected $serviceContainer;

    /**
     * Retrieve a value for named parameter if a service with such name exists
     *
     * @param string $name              The service/parameter name
     * @param ReflectionParameter $ref  Reflection object of the parameter
     *
     * @return mixed|null The service or null if the service does not exists and the parameter is nullable
     *
     * @throws ReflectionException
     * @throws ServiceNotCreatedException
     */
    protected function getParameterValue(string $name, ReflectionParameter $ref)
    {
        $value = null;
        if ($this->serviceContainer->has($name)) {
            $value = $this->serviceContainer->get($name);
        } else if ($ref->isDefaultValueAvailable()) {
            $value = $ref->getDefaultValue();
        } else if (!$ref->allowsNull()) {
            throw new ServiceNotCreatedException(
                'Parameter '.$ref->getName().' is unreachable', 
                3, 
                $ref->getDeclaringClass()->getName()
            );
        }

        return $value;
    }

    /**
     * Prepares a parameter value.
     *
     * Choose whether to use type or reflection name. If the parameter is type-hinted it will use its type to try
     * to create a service. If it not, it will use the parameter name for that.
     *
     * @param ReflectionParameter $ref The parameter reflection object
     *
     * @return mixed|null The parameter value, craeted by the service container
     *
     * @throws ReflectionException
     * @throws ServiceNotCreatedException
     */
    protected function prepareSingleParameter(ReflectionParameter $ref)
    {
        $type = $ref->getType();
        $name = ($type instanceof ReflectionNamedType) 
                    ? $type->getName() 
                    : $ref->getName();

        return $this->getParameterValue($name, $ref);
    }

    /**
     * Prepare values for parameters list
     *
     * @param array $paramRefs List with parameter reflections
     *
     * @return array A list with parameter values, created by the service container
     *
     * @throws ReflectionException
     * @throws ServiceNotCreatedException
     */
    protected function prepareParameters(array $paramRefs): array
    {
        $params = [];
        foreach ($paramRefs as $name => $ref) {
            $params[] = $this->prepareSingleParameter($ref);
        }

        return $params;
    }

    /**
     * Loads and prepare the parameters of the dependency
     *
     * @param ReflectionMethod $ref The reflection of the dependency method
     *
     * @return array|null A list with parameter values or null if there are no parameters
     *
     * @throws ReflectionException
     * @throws ServiceNotCreatedException
     */
    protected function getDependencyParameters(ReflectionMethod $ref)
    {
        if (!$ref->isPublic()) {
            $className = $ref->getDeclaringClass()->getName();
            throw new ServiceNotCreatedException(
                'Cannot create instances of ' .$className,
                2, 
                $className
            );
        }
        $paramRefs = $ref->getParameters();
        if (!$paramRefs) {
            return null;
        }

        return $this->prepareParameters($paramRefs);
    }

    /**
     * Create instance of class by its name
     *
     * @param string $className The class name
     *
     * @return object The created instance
     *
     * @throws ReflectionException
     * @throws ServiceNotCreatedException
     */
    protected function createClassInstance(string $className): object
    {
        $ref = new ReflectionClass($className);
        if ($ref->isAbstract()) {
            throw new ServiceNotCreatedException(
                $className.' is abstract class', 
                1, 
                $className
            );
        }
        if (!$ref->hasMethod('__construct')) {
            return $ref->newInstance();
        }
        $params = $this->getDependencyParameters($ref->getMethod('__construct'));

        return $ref->newInstanceArgs((array) $params);
    }

    /**
     * Create new service
     *
     * @param ContainerInterface $serviceContainer The service container
     * @param string|null        $serviceName      The service name
     *
     * @return object|string|InjectionFactory
     *
     * @throws ReflectionException
     * @throws ServiceNotCreatedException
     */
    public function __invoke(
        ContainerInterface $serviceContainer, 
        $serviceName = null
    ) {
        if (!is_string($serviceName)) {
            throw new InvalidServiceNameException(
                'Service name must be string', 
                1, 
                $serviceName
            );
        }
        if ($serviceName === __CLASS__) {
            return new self();
        }
        $this->serviceContainer = $serviceContainer;

        return (class_exists($serviceName)) ? $this->createClassInstance($serviceName) : $serviceName;
    }
}
