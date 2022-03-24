<?php
namespace MonologConfig\Test\Factory;

use Monolog\Handler\HandlerInterface;
use MonologConfig\Factory\HandlerPluginManagerFactory;
use PHPUnit\Framework\TestCase;
use MonologConfig\Service\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use Prophecy\PhpUnit\ProphecyTrait;

class HandlerPluginManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testPluginManagerCanBeCreated()
    {
        $config = [
            'monolog' => [
                'handler_plugin_manager' => [
                    'services' => [
                        'foo' => $this->prophesize(HandlerInterface::class)->reveal()
                    ]
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $pluginManagerFactory = new HandlerPluginManagerFactory();
        $pluginManager = $pluginManagerFactory->__invoke($container->reveal(), 'foo');

        $this->assertInstanceOf(PluginManager::class, $pluginManager);
        return $pluginManager;
    }

    /**
     * @depends testPluginManagerCanBeCreated
     */
    public function testServiceFooIsHandlerInstance($pluginManager)
    {
        $service = $pluginManager->get('foo');
        $this->assertInstanceOf(HandlerInterface::class, $service);
    }

    /**
     * @depends testPluginManagerCanBeCreated
     */
    public function testServiceBarCanNotBeFound($pluginManager)
    {
        $this->expectException(\Laminas\ServiceManager\Exception\ServiceNotFoundException::class);
        $this->expectExceptionMessage('A plugin by the name "bar" was not found in the plugin manager MonologConfig\Service\PluginManager');
        $service = $pluginManager->get('bar');
    }

    public function testInvalidServiceInstanceThrowsException()
    {
        $config = [
            'monolog' => [
                'handler_plugin_manager' => [
                    'services' => [
                        'foo' => new \stdClass()
                    ]
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $pluginManagerFactory = new HandlerPluginManagerFactory();
        $pluginManager = $pluginManagerFactory->__invoke($container->reveal(), 'foo');

        $this->assertInstanceOf(PluginManager::class, $pluginManager);

        $this->expectException(\Laminas\ServiceManager\Exception\InvalidServiceException::class);
        $service = $pluginManager->get('foo');
    }
}
