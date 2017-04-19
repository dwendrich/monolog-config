<?php
namespace MonologConfig;

use MonologConfig\ConfigProvider;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

/**
 * Class Module
 *
 * @package MonologConfig
 * @author Daniel Wendrich <daniel.wendrich@gmail.com>
 */
class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return (new ConfigProvider)->getModuleConfig();
    }
}
