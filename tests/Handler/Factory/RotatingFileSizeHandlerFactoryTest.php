<?php

namespace MonologConfig\Test\Factory;

use MonologConfig\Handler\RotatingFileSizeHandler;
use MonologConfig\Handler\Factory\RotatingFileSizeHandlerFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class RotatingFileSizeHandlerFactoryTest extends TestCase
{
    public function testCreateHandlerInstance()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $factory = new RotatingFileSizeHandlerFactory();
        $instance = $factory->__invoke(
            $container->reveal(),
            RotatingFileSizeHandler::class,
            [
                'filename' => 'test.log'
            ]
        );

        $this->assertInstanceOf(RotatingFileSizeHandler::class, $instance);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide a 'filename' in handler options.
     */
    public function testCreateHandlerInstanceWithoutFilenameThrowsException()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $factory = new RotatingFileSizeHandlerFactory();
        $instance = $factory->__invoke(
            $container->reveal(),
            RotatingFileSizeHandler::class,
            []
        );
    }
}