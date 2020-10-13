<?php

namespace Rmk\Tests\ServiceContainer;

use Rmk\ServiceContainer\ServiceContainer;
use Psr\Container\ContainerInterface;
use Rmk\ServiceContainer\FactoryInterface;
use Rmk\ServiceContainer\InjectionFactory;
use Rmk\ServiceContainer\Exception\ServiceNotFoundException;
use Rmk\ServiceContainer\Exception\InvalidFactoryException;
use PHPUnit\Framework\TestCase;

class TestFactory implements FactoryInterface {
    public function __invoke(ContainerInterface $serviceContainer, $serviceName = null)
    {
        // TODO: to be implemented...
        return new \stdClass();
    }
}

class TestService
{
    /**
     * @var TestFactory
     */
    private TestFactory $factory;

    public function __construct(TestFactory $factory)
    {
        $this->factory = $factory;
    }

    public function getFactory()
    {
        return $this->factory;
    }
}

class ServiceContainerTest extends TestCase
{

    protected $config;

    protected function setUp(): void
    {
        $this->config = [
            'services' => [
                'factories' => [
                    'test' => static function($sc) { return new \stdClass(); },
                    'test2' => static function($sc) { return new \stdClass(); }
                ],
                'singletones' => [
                    'test2' => false
                ]
            ]
        ];
    }

    public function testInitialServiceLoading(): void
    {
        $service = new \stdClass();
        $container = new ServiceContainer(['test' => $service]);
        $this->assertEquals(1, $container->count());
        $this->assertSame($service, $container->get('test'));
    }

    public function testInit(): void
    {
        $container = new ServiceContainer();
        $container->init($this->config);
        $this->assertEquals(3, $container->getFactories()->count());
        $this->assertEquals(2, $container->getSingletones()->count());
    }

    public function testSingletones(): void
    {
        $container = new ServiceContainer();
        $container->init($this->config);
        $test = $container->get('test');
        $this->assertSame($test, $container->get('test'));
        $test2 = $container->get('test2');
        $this->assertNotSame($test2, $container->get('test2'));
    }

    public function testFactory(): void
    {
        $container = new ServiceContainer();
        $factory = $this->getMockForAbstractClass(FactoryInterface::class);
        $factory->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(static function ($sc) {
                $o = new \stdClass();
                $o->a = 1;
                return $o;
            });

        $this->config['services']['factories']['test3'] = $factory;
        $container->init($this->config);

        $test3 = $container->get('test3');
        $this->assertSame($test3, $container->get('test3'));
        $this->assertEquals(1, $test3->a);
    }

    public function testServiceNotFoundException(): void
    {
        $container = new ServiceContainer();
        $container->init($this->config);
        $this->expectException(ServiceNotFoundException::class);
        $container->get('NotExistingService');
    }

    public function testInvalidFactoryException(): void
    {
        $this->config['services']['factories']['test3'] = 1;
        $container = new ServiceContainer();
        $container->init($this->config);
        $this->expectException(InvalidFactoryException::class);
        $container->get('test3');
    }

    public function testCreateFromFactory(): void
    {
        $this->config['services']['factories']['test3'] = TestFactory::class;
        $container = new ServiceContainer();
        $container->init($this->config);
        $test3 = $container->get('test3');
        $this->assertSame($test3, $container->get('test3'));
    }

    public function testGetInjectionFactory(): void
    {
        $container = new ServiceContainer();
        $f1 = $container->get(InjectionFactory::class);
        $this->assertInstanceOf(InjectionFactory::class, $f1);
    }

    public function testCreateFromInjectionFactory()
    {
        $this->config['services']['factories'][TestService::class] = InjectionFactory::class;
        $f = new TestFactory();
        $container = new ServiceContainer([TestFactory::class => $f]);
        $container->init($this->config);
        $service = $container->get(TestService::class);
        $this->assertInstanceOf(TestService::class, $service);
        $this->assertSame($f, $service->getFactory());
    }

    public function testHas(): void
    {
        $container = new ServiceContainer();
        $container->init($this->config);
        $this->assertTrue($container->has(InjectionFactory::class));
        $this->assertTrue($container->has('test'));
    }
}
