<?php
namespace MonologConfig\Test;

use MonologConfig\Service\PluginManager;
use Monolog\Handler\HandlerInterface;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\ServiceManager;
use Prophecy\PhpUnit\ProphecyTrait;

class PluginManagerTest extends TestCase
{
    use ProphecyTrait;
    
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

    public function testValidatePluginThrowsException()
    {
        $container = $this->prophesize(ServiceManager::class);

        $pluginManager = new PluginManager(
            HandlerInterface::class,
            $container->reveal(),
            []
        );

        $handler = new \stdClass();

        $this->expectException(\Laminas\ServiceManager\Exception\InvalidServiceException::class);
        $pluginManager->validate($handler);
    }
}
