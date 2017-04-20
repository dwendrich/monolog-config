# monolog-config
Simply integrate configurable monolog instances into applications using zend-servicemanager.

Based on https://github.com/neeckeloo/MonologModule. 

## Requirements
* PHP 5.6, PHP 7.0 or higher
* [Monolog 1.11 or higher](https://www.github.com/Seldaek/monolog)
* [Zend Framework Service Manager component 3.0.3 or higher](https://github.com/zendframework/zend-servicemanager)

## Installation
MonologConfig can be installed with composer. For information on how to get composer or how to use it, please refer to
[getcomposer.org](http://getcomposer.org).

Installation via command line:
```sh
$ php composer.phar require dwendrich/monolog-config:~0.1
```

Installation via `composer.json` file:
```json
{
    "require": {
        "dwendrich/monolog-config": "~0.1"
    }
}
```
To enable creation of logger instances through zend-servicemanager, three factories have to be registered.

As part of a zend-expressive application, for example, you add `ConfigProvider::class` to `config/config.php`:
```php
$aggregator = new ConfigAggregator([
 
    MonologConfig\ConfigProvider::class,
    
    // ... other stuff goes here 

    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new PhpFileProvider('config/autoload/{{,*.}global,{,*.}local}.php'),
    

    // Load development config if it exists
    new PhpFileProvider('config/development.config.php'),
], $cacheConfig['config_cache_path']);
```
In case you implement a zend-framework application, add `MonologConfig` key to `config/modules.config.php` or
the modules section in `config/application.config.php` respectively.

## Usage
### Logger configuration
In your application or module configuration simply add a key to the `monolog` section below the `logger` key.

For example, a logger with the key `Application\Log` can be set up like this:
```php
return [
    'monolog' => [
        'logger' => [
            'Application\Log' => [
                'channel' => 'default',
            ],
        ],
    ],
];
```

The minimum requirement to define a logger is the `channel` attribute. Channels are used to assign log records to
certain parts of the application.

### Handler configuration
The logger by itself does not know how to handle log records. This is delegated to one or more handlers. For further
information about handlers, formatters and their usage please refer to the
[monolog documentation](https://github.com/Seldaek/monolog).

The code below registers two handlers with the logger:
```php
return [
    'monolog' => [
        'logger' => [
            'Application\Log' => [
                'channel' => 'default',
                'handlers' => [
                    'stream' => [
                        'class' => StreamHandler::class,
                        'options' => [
                            'path' => 'data/log/application.log',
                            'level' => Logger::DEBUG,
                        ],
                    ],
                    'fire_php' => new \Monolog\Handler\FirePHPHandler(),
                ],
            ],
        ],
    ],
];
```
The handler definition has to either be an array containing at least a `class` attribute or an instance of
`Monolog\Handler\HandlerInterface`.

### Adding processors
Processors allow to add extra information to the log record. The following code illustrates how to add processors:
```php
return [
    'monolog' => [
        'logger' => [
            'Application\Log' => [
                'channel' => 'default',
                'handlers' => [
                    'stream' => [
                        'class' => StreamHandler::class,
                        'options' => [
                            'path' => 'data/log/application.log',
                            'level' => Logger::DEBUG,
                        ],
                    ],
                ],
                'processors' => [
                    UidProcessor::class,
                    new \Monolog\Processor\IntrospectionProcessor(Logger::ERROR),
                ],
            ],
        ],
    ],
];
```
To add a processor, you can use a string to create an instance or pass an object. Processors have to be callable in
order to work with monolog.

### Adding formatters
Formatters are used to format the output of a log record. They can be attached to a handler.
```php
return [
    'monolog' => [
        'logger' => [
            'Application\Log' => [
                'channel' => 'default',
                'handlers' => [
                    'stream' => [
                        'class' => StreamHandler::class,
                        'options' => [
                            'path' => 'data/log/application.log',
                            'level' => Logger::DEBUG,
                        ],
                        'formatter' => [
                            'class' => LineFormatter::class,
                            'options' => [
                                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```
A Formatter may be added by providing an array containg a `class` attribute and optionally an `options` key containing
constructor parameter.

Alternatively an instance of `Monolog\Formatter\FormatterInterface` can be passed in as argument:
```php
return [
    'monolog' => [
        'logger' => [
            'Application\Log' => [
                'channel' => 'default',
                'handlers' => [
                    'stream' => [
                        'class' => StreamHandler::class,
                        'options' => [
                            'path' => 'data/log/application.log',
                            'level' => Logger::DEBUG,
                        ],
                        'formatter' => new LineFormatter(
                            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
                        ),
                    ],
                ],
            ],
        ],
    ],
];
```
### Retrieving a logger instance
You can retrieve a logger instance from zend-servicemanager by its configuration key, for example:
```php
/** @var Zend\ServiceManager\ServiceManager $container */
$logger = $container->get('Application\Log');
$logger->debug('debug message');
```