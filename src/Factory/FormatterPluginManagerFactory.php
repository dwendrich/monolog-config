<?php

namespace MonologConfig\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Monolog\Formatter\FormatterInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use MonologConfig\Service\PluginManager;

/**
 * Class FormatterPluginManagerFactory
 *
 * @package MonologConfig\Factory
 * @author Daniel Wendrich <daniel.wendrich@gmail.com>
 */
class FormatterPluginManagerFactory implements FactoryInterface
{
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
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        return new PluginManager(
            FormatterInterface::class,
            $container,
            isset($config['monolog']['formatter_plugin_manager'])
                ? $config['monolog']['formatter_plugin_manager']
                : []
        );
    }
}