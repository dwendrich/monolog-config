<?php
namespace MonologConfig\Test\Factory;

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
use MonologConfig\Handler\RotatingFileSizeHandler;
use MonologConfig\Handler\Factory\RotatingFileSizeHandlerFactory;
use MonologConfig\Service\PluginManager;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\ServiceManager;
use Prophecy\PhpUnit\ProphecyTrait;

class LoggerAbstractFactoryTest extends TestCase
{
    use ProphecyTrait;

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

    public function testCreateLoggerWithHandlerUsingPluginManager()
    {
        $config = [
            'monolog' => [
                'logger' => [
                    'foo' => [
                        'channel' => 'default',
                        'handlers' => [
                            [
                                'class' => RotatingFileSizeHandler::class,
                                'options' => [
                                    'filename' => 'data/log/stream.log',
                                    'filesize' => 1.0,
                                    'level' => Logger::DEBUG
                                ],
                            ],
                        ],
                    ],
                ],
                'handler_plugin_manager' => [
                    'factories' => [
                        RotatingFileSizeHandler::class => RotatingFileSizeHandlerFactory::class,
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
                $config['monolog']['handler_plugin_manager']
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

        $handler = array_pop($handlers);
        $this->assertInstanceOf(RotatingFileSizeHandler::class, $handler);
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Handler configuration must be provided as array.');

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }

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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You must provide a handler class.');

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }

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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Logger handler "TestHandler" does not exists.');

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

    public function testCreateLoggerWithFormatterConfigThrowsException()
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
                                'formatter' => LineFormatter::class,
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter configuration must be provided as array.');

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }

    public function testCreateLoggerWithEmptyFormatterConfigThrowsException()
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
                                'formatter' => [],
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You must provide a formatter class.');

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }

    public function testCreateLoggerWithFormatterConfigAndNotExistentClassThrowsException()
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
                                    'class' => 'NotExistentPhantasyFormatterClassName',
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Logger formatter "NotExistentPhantasyFormatterClassName" does not exists');

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
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

    public function testCreateLoggerWithConfiguredProcessorsThrowsException()
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
                            \stdClass::class
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Processors must be provided as class name or a callable instance.');

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }

    public function testCreateLoggerWithConfiguredNonExistentProcessorsThrowsException()
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
                            'NotExistentPhantasyProcessorClassName',
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Processor class "NotExistentPhantasyProcessorClassName" does not exists');

        /** @var Logger $logger */
        $logger = $abstractFactory->__invoke($container->reveal(), 'foo', []);
    }
}
