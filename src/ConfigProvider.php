<?php
namespace MonologConfig;

use MonologConfig\Factory\FormatterPluginManagerFactory;
use MonologConfig\Factory\HandlerPluginManagerFactory;
use MonologConfig\Factory\LoggerAbstractFactory;

/**
 * Class ConfigProvider
 *
 * @package MonologConfig
 * @author Daniel Wendrich <daniel.wendrich@gmail.com>
 */
class ConfigProvider
{
    public function __invoke()
    {
        return [
            // provide service manager configuration
            'dependencies' => $this->getDependencies(),

            // config key to add custom handler/formatter plugins to monolog plugin managers
            'monolog' => $this->getMonologPluginManagerConfig(),
        ];
    }

    public function getModuleConfig()
    {
        return [
            'service_manager' => $this->getDependencies(),
            'monolog' => $this->getMonologPluginManagerConfig()
        ];
    }

    private function getMonologPluginManagerConfig()
    {
        return [
            'handler_plugin_manager' => [

            ],

            'formatter_plugin_manager' => [

            ],
        ];
    }

    private function getDependencies()
    {
        return [
            'invokables' => [
            ],

            'factories'  => [
                'MonologConfig\Service\FormatterPluginManager' => FormatterPluginManagerFactory::class,
                'MonologConfig\Service\HandlerPluginManager' => HandlerPluginManagerFactory::class,
            ],

            'abstract_factories' => [
                LoggerAbstractFactory::class,
            ],
        ];
    }
}
