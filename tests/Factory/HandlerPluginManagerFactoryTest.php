<?php
namespace MonologConfigTest\Factory;

use Monolog\Handler\HandlerInterface;
use MonologConfig\Factory\HandlerPluginManagerFactory;
use PHPUnit\Framework\TestCase;
use MonologConfig\Service\PluginManager;
use Zend\ServiceManager\ServiceManager;

class HandlerPluginManagerFactoryTest extends TestCase
{
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
     * @expectedException \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @expectedExceptionMessage A plugin by the name "bar" was not found in the plugin manager MonologConfig\Service\PluginManager
     */
    public function testServiceBarCanNotBeFound($pluginManager)
    {
        $service = $pluginManager->get('bar');
    }

    /**
     * @expectedException \Zend\ServiceManager\Exception\InvalidServiceException
     */
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

        $service = $pluginManager->get('foo');
    }
}