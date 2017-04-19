#monolog-config
Simply integrate configurable monolog instances into applications using zend-servicemanager.

Based on https://github.com/neeckeloo/MonologModule. 

##Requirements
* PHP 5.6, PHP 7.0 or higher
* [Monolog 1.11 or higher](https://www.github.com/Seldaek/monolog)
* [Zend Framework Service Manager component 3.0.3 or higher](https://github.com/zendframework/zend-servicemanager)

##Installation
MonologConfig can be installed with Composer. For information on how to get composer or how to use it, please refer to [getcomposer.org](http://getcomposer.org).

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
In case you implement a zend-framework application, add `MonologConfig` key to `config/modules.config.php` or `config/application.config.php` respectively.

##Usage
###Logger configuration
To configure a logger which can be retrieved with the key through the servicemanager