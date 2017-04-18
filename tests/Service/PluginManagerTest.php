<?php
namespace MonologConfigTest;

use MonologConfig\Service\PluginManager;
use Monolog\Handler\HandlerInterface;
use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\ServiceManager;

class PluginManagerTest extends TestCase
{
    public function testValidatePlugin()
    {
        $container = $this->prophesize(ServiceManager::class);

        $pluginManager = new PluginManager(
            HandlerInterface::class,
            $container->reveal(),
            []
        );

        $handler = $this->prophesize(HandlerInterface::class);
        $pluginManager->validate($handler->reveal());

        $this->assertTrue(true);
    }

    /**
     * @expectedException \Zend\ServiceManager\Exception\InvalidServiceException
     */
    public function testValidatePluginThrowsException()
    {
        $container = $this->prophesize(ServiceManager::class);

        $pluginManager = new PluginManager(
            HandlerInterface::class,
            $container->reveal(),
            []
        );

        $handler = new \stdClass();
        $pluginManager->validate($handler);
    }
}