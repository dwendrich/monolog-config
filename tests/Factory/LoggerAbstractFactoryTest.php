<?php
namespace MonologConfigTest\Factory;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use MonologConfig\Factory\LoggerAbstractFactory;
use MonologConfig\Service\PluginManager;
use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\ServiceManager;

class LoggerAbstractFactoryTest extends TestCase
{
    public function canCreateServiceWithNameProvider()
    {
        return [
            ['Log\Foo', true],
            ['Log\Bar', false],
            ['Log\Baz', false]
        ];
    }

    /**
     * @dataProvider canCreateServiceWithNameProvider
     */
    public function testCanCreateServiceWithName($name, $expected)
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'Log\Foo' => [
                        'channel' => 'default',
                    ],
                    'Log\Bar' => [],
                ]
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $abstractFactory = new LoggerAbstractFactory();
        $bool = $abstractFactory->canCreate($container->reveal(), $name);
        $this->assertSame($expected, $bool);
    }

    public function testCreateLoggerWithHandlerConfigWorks()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            [
                                'class' => StreamHandler::class,
                                'options' => [
                                    'path' => 'data/log/stream.log',
                                    'level' => Logger::DEBUG
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $container->get('MonologConfig\Service\HandlerPluginManager')
            ->willReturn(new PluginManager(
                HandlerInterface::class,
                $container->reveal(),
                []
            ));

        $container->get('MonologConfig\Service\FormatterPluginManager')
            ->willReturn(new PluginManager(
                FormatterInterface::class,
                $container->reveal(),
                []
            ));

        $abstractFactory = new LoggerAbstractFactory();

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);

        $handlers = $logger->getHandlers();
        $this->assertTrue(is_array($handlers));
        $this->assertEquals(1, count($handlers));
        $this->assertContainsOnlyInstancesOf(HandlerInterface::class, $handlers);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Handler configuration must be provided as array.
     */
    public function testCreateLoggerWithHandlerConfigFailsBecauseOfWrongInstance()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            new \stdClass(),
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $container->get('MonologConfig\Service\HandlerPluginManager')
            ->willReturn(new PluginManager(
                HandlerInterface::class,
                $container->reveal(),
                []
            ));

        $container->get('MonologConfig\Service\FormatterPluginManager')
            ->willReturn(new PluginManager(
                FormatterInterface::class,
                $container->reveal(),
                []
            ));

        $abstractFactory = new LoggerAbstractFactory();

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You must provide a handler class.
     */
    public function testCreateLoggerWithHandlerConfigFailsBecauseOfNotSpecifiedClassKey()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            [

                            ]
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $container->get('MonologConfig\Service\HandlerPluginManager')
            ->willReturn(new PluginManager(
                HandlerInterface::class,
                $container->reveal(),
                []
            ));

        $container->get('MonologConfig\Service\FormatterPluginManager')
            ->willReturn(new PluginManager(
                FormatterInterface::class,
                $container->reveal(),
                []
            ));

        $abstractFactory = new LoggerAbstractFactory();

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Logger handler "TestHandler" does not exists.
     */
    public function testCreateLoggerWithHandlerConfigFailsBecauseOfSpecifiedClassMissing()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            [
                                'class' => 'TestHandler'
                            ]
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $container->get('MonologConfig\Service\HandlerPluginManager')
            ->willReturn(new PluginManager(
                HandlerInterface::class,
                $container->reveal(),
                []
            ));

        $container->get('MonologConfig\Service\FormatterPluginManager')
            ->willReturn(new PluginManager(
                FormatterInterface::class,
                $container->reveal(),
                []
            ));

        $abstractFactory = new LoggerAbstractFactory();

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }

    public function testCreateLoggerWithHandlerInstanceWorks()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            new FirePHPHandler(),
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $container->get('MonologConfig\Service\HandlerPluginManager')
            ->willReturn(new PluginManager(
                HandlerInterface::class,
                $container->reveal(),
                []
            ));

        $container->get('MonologConfig\Service\FormatterPluginManager')
            ->willReturn(new PluginManager(
                FormatterInterface::class,
                $container->reveal(),
                []
            ));

        $abstractFactory = new LoggerAbstractFactory();

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);

        $handlers = $logger->getHandlers();
        $this->assertTrue(is_array($handlers));
        $this->assertEquals(1, count($handlers));
        $this->assertContainsOnlyInstancesOf(HandlerInterface::class, $handlers);
    }

    public function testCreateLoggerWithFormatterConfigWorks()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            [
                                'class' => StreamHandler::class,
                                'options' => [
                                    'path' => 'data/log/stream.log',
                                    'level' => Logger::DEBUG
                                ],
                                'formatter' => [
                                    'class' => LineFormatter::class
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $container->get('MonologConfig\Service\HandlerPluginManager')
            ->willReturn(new PluginManager(
                HandlerInterface::class,
                $container->reveal(),
                []
            ));

        $container->get('MonologConfig\Service\FormatterPluginManager')
            ->willReturn(new PluginManager(
                FormatterInterface::class,
                $container->reveal(),
                []
            ));

        $abstractFactory = new LoggerAbstractFactory();

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);

        $handlers = $logger->getHandlers();

        /** @var HandlerInterface $handler */
        $handler = $handlers[0];
        $this->assertInstanceOf(\Monolog\Formatter\LineFormatter::class, $handler->getFormatter());
    }

    public function testCreateServiceWithFormatterInstanceWorks()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            [
                                'class' => StreamHandler::class,
                                'options' => [
                                    'path' => 'data/log/stream.log',
                                    'level' => Logger::DEBUG
                                ],
                                'formatter' => new LineFormatter(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $container->get('MonologConfig\Service\HandlerPluginManager')
            ->willReturn(new PluginManager(
                HandlerInterface::class,
                $container->reveal(),
                []
            ));

        $container->get('MonologConfig\Service\FormatterPluginManager')
            ->willReturn(new PluginManager(
                FormatterInterface::class,
                $container->reveal(),
                []
            ));

        $abstractFactory = new LoggerAbstractFactory();

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);

        $handlers = $logger->getHandlers();

        /** @var HandlerInterface $handler */
        $handler = $handlers[0];
        $this->assertInstanceOf(\Monolog\Formatter\LineFormatter::class, $handler->getFormatter());
    }

    public function testCreateLoggerWithConfiguredProcessorsWorks()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            new NullHandler(),
                        ],
                        'processors' => [
                            UidProcessor::class,
                            WebProcessor::class,
                        ]
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ServiceManager::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $container->get('MonologConfig\Service\HandlerPluginManager')
            ->willReturn(new PluginManager(
                HandlerInterface::class,
                $container->reveal(),
                []
            ));

        $container->get('MonologConfig\Service\FormatterPluginManager')
            ->willReturn(new PluginManager(
                FormatterInterface::class,
                $container->reveal(),
                []
            ));

        $abstractFactory = new LoggerAbstractFactory();

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);

        $processors = $logger->getProcessors();
        $this->assertTrue(is_array($processors));
        $this->assertEquals(2, count($processors));
    }
}