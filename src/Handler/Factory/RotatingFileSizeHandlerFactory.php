<?php

namespace MonologConfig\Handler\Factory;

use MonologConfig\Handler\RotatingFileSizeHandler;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Monolog\Logger;

class RotatingFileSizeHandlerFactory
{
    /**
     * @var array
     */
    protected $options = [
        'filesize'       => 1.0,
        'compression'    => 0,
        'level'          => Logger::DEBUG,
        'bubble'         => true,
        'filePermission' => null,
        'useLocking'     => false
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    protected function validateOptions()
    {
        if (empty($this->options['filename'])) {
            throw new \InvalidArgumentException(
                "You need to provide a 'filename' in handler options."
            );
        }
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($options !== null) {
            $this->options = array_merge($this->options, $options);
        }

        $this->validateOptions();

        return new RotatingFileSizeHandler(
            $this->options['filename'],
            $this->options['filesize'],
            $this->options['compression'],
            $this->options['level'],
            $this->options['bubble'],
            $this->options['filePermission'],
            $this->options['useLocking']
        );
    }
}
