<?php
namespace MonologConfigTest\Factory;

use Monolog\Formatter\FormatterInterface;
use MonologConfig\Factory\FormatterPluginManagerFactory;
use PHPUnit\Framework\TestCase;
use MonologConfig\Service\PluginManager;
use Zend\ServiceManager\ServiceManager;

class FormatterPluginManagerFactoryTest extends TestCase
{
    public function testPluginManagerCanBeCreated()
    {
        $config = [
            'monolog' => [
                'formatter_plugin_manager' => [
                    'services' => [
                        'foo' => $this->prophesize(FormatterInterface::class)->reveal()
                    ]
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $pluginManagerFactory = new FormatterPluginManagerFactory();
        $pluginManager = $pluginManagerFactory->__invoke($container->reveal(), 'foo');

        $this->assertInstanceOf(PluginManager::class, $pluginManager);
        return $pluginManager;
    }

    /**
     * @depends testPluginManagerCanBeCreated
     */
    public function testServiceFooIsFormatterInstance($pluginManager)
    {
        $service = $pluginManager->get('foo');
        $this->assertInstanceOf(FormatterInterface::class, $service);
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
                'formatter_plugin_manager' => [
                    'services' => [
                        'foo' => new \stdClass()
                    ]
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $pluginManagerFactory = new FormatterPluginManagerFactory();
        $pluginManager = $pluginManagerFactory->__invoke($container->reveal(), 'foo');

        $this->assertInstanceOf(PluginManager::class, $pluginManager);

        $service = $pluginManager->get('foo');
    }
}