<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObject;

use MongoClient;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * MapperFactory is Zend Framework 2 service that returns Mapper object ready
 * for use. It expects configuration to have 'mongoObjectMapper' variable that
 * holds all information needed for start working against Mongo DB.
 *
 * Allowed fields of mongoObjectMapper:
 * uri - URI of Mongo that is passed to MongoClient class constructor
 * options - array of options that is passed to MongoClient class constructor
 * database - name of database in Mongo to use
 * modelsNamespace - name of namespace where derivatives of MongoObject\Object
 * are defined
 */
class MapperFactory implements FactoryInterface
{
    /**
     * Create service - return new Mapper object
     * @param ServiceLocatorInterface $serviceLocator service locator
     * @return Mapper instance of Mapper object
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config')['mongoObjectMapper'];
        $client = self::constructMongoClient(@$config['uri'], @$config['options']);

        $mapperClass = "MongoObject\Mapper";
        if (isset($config['mapperClass'])) {
            $mapperClass = $config['mapperClass'];
        }

        return new $mapperClass($client->selectDb($config['database']), @$config['modelsNamespace']);
    }

    /**
     * Construct MongoClient object
     * @param string|null $uri optional URI of Mongo
     * @param array|null $options optional array of options
     * @return MongoClient new MongoClient object
     */
    private static function constructMongoClient($uri, $options)
    {
        if (isset($uri)) {
            if (isset($options)) {
                return new MongoClient($uri, $options);
            }
            return new MongoClient($uri);
        }
        return new MongoClient();
    }
}
