<?php

namespace MonologConfig\Test\Factory;

use MonologConfig\Handler\RotatingFileSizeHandler;
use MonologConfig\Handler\Factory\RotatingFileSizeHandlerFactory;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RotatingFileSizeHandlerFactoryTest extends TestCase
{
    use ProphecyTrait;

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

    public function testCreateHandlerInstanceWithoutFilenameThrowsException()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $factory = new RotatingFileSizeHandlerFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("You need to provide a 'filename' in handler options.");

        $instance = $factory->__invoke(
            $container->reveal(),
            RotatingFileSizeHandler::class,
            []
        );
    }
}
