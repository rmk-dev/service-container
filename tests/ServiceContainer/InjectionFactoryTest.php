<?php

namespace Rmk\Tests\ServiceContainer;

use Rmk\ServiceContainer\InjectionFactory;
use Psr\Container\ContainerInterface;
use Rmk\ServiceContainer\Exception\ServiceNotCreatedException;
use Rmk\ServiceContainer\Exception\ServiceNotFoundException;
use Rmk\ServiceContainer\Exception\InvalidFactoryException;
use Rmk\ServiceContainer\Exception\InvalidServiceNameException;
use PHPUnit\Framework\TestCase;

class WithoutConstructor {}

abstract class AbstractClass {}

class WithoutConstructorParams {
    public $a;
    public function __construct() { $this->a = 'asd'; }
}

class WithNonPublicConstructor {
    protected function __construct() {}
}

class WithTypehintedParams {
    public $a;
    public $b;
    public function __construct(
        WithoutConstructor $a, 
        WithoutConstructorParams $b
    ) {
        $this->a = $a;
        $this->b = $b;
    }
}

class WithTypehintedDefaultParams {
    public $a;
    public $b;
    public function __construct(
        WithoutConstructor $a, 
        string $b = 'B'
    ) {
        $this->a = $a;
        $this->b = $b;
    }
}

class WithTypehintedNullableParams {
    public $a;
    public $b;
    public function __construct(
        WithoutConstructor $a, 
        ?string $b
    ) {
        $this->a = $a;
        $this->b = $b;
    }
}
// .........

class WithParamNameParams {
    public $a;
    public $b;
    public function __construct(
        $notClassService, 
        $b
    ) {
        $this->a = $notClassService;
        $this->b = $b;
    }
}

class WithParamNameDefaultParams {
    public $a;
    public $b;
    public function __construct(
        $notClassService, 
        $b = 'B'
    ) {
        $this->a = $notClassService;
        $this->b = $b;
    }
}

class UnreachableParam {
    public function __construct(int $unexistedService) {}
}

class InjectionFactoryTest extends TestCase 
{

    protected $serviceContainer;

    protected $factory;

    protected $config;

    protected function setUp(): void
    {
        $this->config = [
            InjectionFactory::class => new InjectionFactory(),
            'notClassService' => 'notClassService',
            'b' => 'B',
            WithoutConstructor::class => new WithoutConstructor(),
            WithoutConstructorParams::class => new WithoutConstructorParams(),
        ];
        $config =& $this->config;
        $this->serviceContainer = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->serviceContainer
            ->method('get')
            ->willReturnCallback(static function ($key) use (&$config) {
                if (array_key_exists($key, $config)) {
                    return $config[$key];
                } else {
                    return null;
                }
            });

        $this->serviceContainer
            ->method('has')
            ->willReturnCallback(static function ($key) use ($config) {
                return array_key_exists($key, $config);
            });

        $this->factory = new InjectionFactory();
        
    }

    public function testCreateSelf(): void
    {
        $this->assertInstanceOf(
            InjectionFactory::class,
            $this->factory->__invoke($this->serviceContainer, InjectionFactory::class)
        );
    }

    public function testInvalidServiceNameException(): void
    {
        $this->expectException(InvalidServiceNameException::class);
        $this->factory->__invoke($this->serviceContainer, 1);
    }

    public function testUnexistingClassCreate(): void
    {
        $serviceName = 'notClassService';
        $this->assertEquals($serviceName, $this->factory->__invoke($this->serviceContainer, $serviceName));
    }

    public function testCreateAbstractClass(): void
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->factory->__invoke($this->serviceContainer, AbstractClass::class);
    }

    public function testCreateWithoutConstructor(): void
    {
        $this->assertInstanceOf(
            WithoutConstructor::class, 
            $this->factory->__invoke($this->serviceContainer, WithoutConstructor::class)
        );
    }

    public function testCreateWithoutConstructorParameters(): void
    {
        $service = $this->factory->__invoke($this->serviceContainer, WithoutConstructorParams::class);
        $this->assertInstanceOf(
            WithoutConstructorParams::class, 
            $service
        );
        $this->assertEquals('asd', $service->a);
    }

    public function testCreateWithNonPublicConstructor(): void
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->factory->__invoke($this->serviceContainer, WithNonPublicConstructor::class);
    }

    public function testWithTypehintedServiceParameters(): void
    {
        $service = $this->factory->__invoke($this->serviceContainer, WithTypehintedParams::class);
        $this->assertInstanceOf(
            WithTypehintedParams::class, 
            $service
        );
        $this->assertInstanceOf(WithoutConstructor::class, $service->a);
        $this->assertInstanceOf(WithoutConstructorParams::class, $service->b);
    }

    public function testWithTypehintedDefaultParameters(): void
    {
        $service = $this->factory->__invoke($this->serviceContainer, WithTypehintedDefaultParams::class);
        $this->assertInstanceOf(
            WithTypehintedDefaultParams::class, 
            $service
        );
        $this->assertInstanceOf(WithoutConstructor::class, $service->a);
        $this->assertEquals('B', $service->b);
    }

    public function testWithTypehintedNullableParameters(): void
    {
        $service = $this->factory->__invoke($this->serviceContainer, WithTypehintedNullableParams::class);
        $this->assertInstanceOf(
            WithTypehintedNullableParams::class, 
            $service
        );
        $this->assertInstanceOf(WithoutConstructor::class, $service->a);
        $this->assertNull($service->b);
    }

    public function testWithParamNameServiceParameters(): void
    {
        $service = $this->factory->__invoke($this->serviceContainer, WithParamNameParams::class);
        $this->assertInstanceOf(
            WithParamNameParams::class, 
            $service
        );
        $this->assertEquals('notClassService', $service->a);
        $this->assertEquals('B', $service->b);
    }

    public function testWithParamNameDefaultParameters(): void
    {
        $service = $this->factory->__invoke($this->serviceContainer, WithParamNameDefaultParams::class);
        $this->assertInstanceOf(
            WithParamNameDefaultParams::class, 
            $service
        );
        $this->assertEquals('notClassService', $service->a);
        $this->assertEquals('B', $service->b);
    }

    public function testParameterIsUnreachable(): void
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->factory->__invoke($this->serviceContainer, UnreachableParam::class);
    }

}
