<?php
namespace MonologConfig\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use MonologConfig\Service\PluginManager;

/**
 * Class LoggerFactory
 *
 * @package MonologConfig\Factory
 * @author Daniel Wendrich <daniel.wendrich@gmail.com>
 */
class LoggerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * @var PluginManager
     */
    private $handlerPluginManager;

    /**
     * @var PluginManager
     */
    private $formatterPluginManager;

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $this->getLoggerInstanceConfig($container, $requestedName);
        return !empty($config['channel']);
    }

    private function getLoggerInstanceConfig(ContainerInterface $container, $name)
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        $instanceConfig = isset($config['monolog']['logger'][$name])
            ? $config['monolog']['logger'][$name]
            : [];

        return $instanceConfig;
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $this->getLoggerInstanceConfig($container, $requestedName);

        $this->handlerPluginManager = $container->get('MonologConfig\Service\HandlerPluginManager');
        $this->formatterPluginManager = $container->get('MonologConfig\Service\FormatterPluginManager');

        $logger = new Logger($config['channel']);
        $this->composeLogger($logger, $config);

        return $logger;
    }

    /**
     * @param Logger $logger
     * @param array $config
     * @return Logger
     * @throws \InvalidArgumentException
     */
    private function composeLogger(Logger $logger, array $config)
    {
        /*
         * If handler configuration is provided, create and push
         * handler to logger instance.
         */
        if (isset($config['handlers']) && is_array($config['handlers'])) {
            foreach ($config['handlers'] as $handler) {
                $logger->pushHandler($this->createHandler($handler));
            }
        }

        /**
         * If processor configuration is provided, create and push
         * processor to logger instance.
         */
        if (isset($config['processors']) && is_array($config['processors'])) {
            foreach ($config['processors'] as $processor) {
                $logger->pushProcessor($this->createProcessor($processor));
            }
        }

        return $logger;
    }

    private function createHandler($handler)
    {
        if ($handler instanceof HandlerInterface) {
            return $handler;
        }

        if (!is_array($handler)) {
            throw new \InvalidArgumentException(
                'Handler configuration must be provided as array.'
            );
        }

        if (!isset($handler['class'])) {
            throw new \InvalidArgumentException(
                'You must provide a handler class.'
            );
        }

        if (!class_exists($handler['class'])) {
            throw new \InvalidArgumentException(sprintf(
                'Logger handler "%s" does not exists.',
                $handler['class']
            ));
        }

        $instance = $this->createInstance($handler, $this->handlerPluginManager);

        if (isset($handler['formatter'])) {
            $formatter = $this->createFormatter($handler['formatter']);
            $instance->setFormatter($formatter);
        }

        return $instance;
    }

    private function createFormatter($formatter)
    {
        if ($formatter instanceof FormatterInterface) {
            return $formatter;
        }

        if (!is_array($formatter)) {
            throw new \InvalidArgumentException(
                'Formatter configuration must be provided as array.'
            );
        }

        if (!isset($formatter['class'])) {
            throw new \InvalidArgumentException(
                'You must provide a formatter class.'
            );
        }

        if (!class_exists($formatter['class'])) {
            throw new \InvalidArgumentException(sprintf(
                'Logger formatter "%s" does not exists',
                $formatter['class']
            ));
        }

        return $this->createInstance($formatter, $this->formatterPluginManager);
    }

    private function createInstance(array $config, AbstractPluginManager $pluginManager = null)
    {
        $options = (isset($config['options']) && is_array($config['options']))
            ? $config['options']
            : [];

        // try to instantiate via plugin manager
        if ($pluginManager->has($config['class'])) {
            return $pluginManager->get($config['class'], $options);
        }

        // if options, try to instantiate via reflection
        if (!empty($options)) {
            $reflection = new \ReflectionClass($config['class']);
            return call_user_func_array([$reflection, 'newInstance'], array_values($options));
        }

        // create class w/o options
        $class = $config['class'];
        return new $class();
    }

    private function createProcessor($processor)
    {
        if (is_string($processor)) {
            if (!class_exists($processor)) {
                throw new \InvalidArgumentException(sprintf(
                    'Processor class "%s" does not exists',
                    $processor
                ));
            }

            $processor = new $processor();
        }

        if (is_callable($processor)) {
            return $processor;
        }

        throw new \InvalidArgumentException(
            'Processors must be provided as class name or a callable instance.'
        );
    }
}
