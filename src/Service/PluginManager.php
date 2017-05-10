<?php
namespace MonologConfig\Service;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;

/**
 * Class PluginManager
 *
 * @package MonologConfig\Service
 * @author Daniel Wendrich <daniel.wendrich@gmail.com>
 */
class PluginManager extends AbstractPluginManager
{
    /**
     * PluginManager constructor.
     *
     * @param string $expectedInstance
     * @param \Interop\Container\ContainerInterface|null|\Zend\ServiceManager\ConfigInterface
     *        $configInstanceOrParentLocator
     * @param array $config
     */
    public function __construct($expectedInstance, $configInstanceOrParentLocator = null, array $config = [])
    {
        parent::__construct($configInstanceOrParentLocator, $config);
        $this->instanceOf = $expectedInstance;
    }
}
