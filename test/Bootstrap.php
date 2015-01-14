<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */

namespace MongoObjectTest;

use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use RuntimeException;
use MongoObject\MapperService;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

class Bootstrap
{
    protected static $serviceManager;

    public static function init()
    {
        if (is_readable(__DIR__ . '/TestConfig.php')) {
            $testConfig = include __DIR__ . '/TestConfig.php';
        } else {
            $testConfig = include __DIR__ . '/TestConfig.php.dist';
        }

        static::initAutoloader();

        $baseConfig = array();
        $config = ArrayUtils::merge($baseConfig, $testConfig);

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('config', $config);
        $serviceManager->setFactory('mongo', 'MongoObject\MapperFactory');
        static::$serviceManager = $serviceManager;
    }

    public static function getServiceManager()
    {
        return static::$serviceManager;
    }

    protected static function initAutoloader()
    {
        $vendorPath = __DIR__ . "/../vendor";
        if (! is_readable($vendorPath . '/autoload.php')) {
            throw new RuntimeException('Missing autoloader. Run `php composer.phar install` in the module root.');
        }
        include $vendorPath . '/autoload.php';
    }
}

Bootstrap::init();
