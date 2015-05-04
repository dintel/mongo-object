<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjecttest;

use MongoId;
use MongoDate;

class MapperFactoryTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testMapperFactory()
    {
        $services = Bootstrap::getServiceManager();
        $config   = $services->get('config');
        $configBackup = $config;
        $mapper1  = $services->get('mongo');

        unset($config['mongoObjectMapper']['options']);
        $services->setAllowOverride(true);
        $services->setService('config', $config);
        $services->setService('mongo', null);
        $services->setFactory('mongo', 'MongoObject\MapperFactory');
        $services->setAllowOverride(false);
        $mapper2 = $services->get('mongo');

        unset($config['mongoObjectMapper']['uri']);
        $services->setAllowOverride(true);
        $services->setService('config', $config);
        $services->setService('mongo', null);
        $services->setFactory('mongo', 'MongoObject\MapperFactory');
        $services->setAllowOverride(false);
        $mapper3 = $services->get('mongo');

        $config['mongoObjectMapper']['mapperClass'] = 'MongoObjectTest\CustomMapper';
        $services->setAllowOverride(true);
        $services->setService('config', $config);
        $services->setService('mongo', null);
        $services->setFactory('mongo', 'MongoObject\MapperFactory');
        $services->setAllowOverride(false);
        $mapper4 = $services->get('mongo');

        $this->assertInstanceOf('MongoObject\Mapper', $mapper1);
        $this->assertInstanceOf('MongoObject\Mapper', $mapper2);
        $this->assertInstanceOf('MongoObject\Mapper', $mapper3);
        $this->assertInstanceOf('MongoObject\Mapper', $mapper4);
        $this->assertNotSame($mapper1, $mapper2);
        $this->assertNotSame($mapper1, $mapper3);
        $this->assertNotSame($mapper1, $mapper4);
        $this->assertNotSame($mapper2, $mapper3);
        $this->assertNotSame($mapper2, $mapper4);
        $this->assertNotSame($mapper3, $mapper4);

        $this->assertFalse(
            method_exists($mapper1, 'customTestFunction'),
            'customTestFunction must not exist in default Mapper class (mapper1 object)'
        );
        $this->assertFalse(
            method_exists($mapper2, 'customTestFunction'),
            'customTestFunction must not exist in default Mapper class (mapper2 object)'
        );
        $this->assertFalse(
            method_exists($mapper3, 'customTestFunction'),
            'customTestFunction must not exist in default Mapper class (mapper3 object)'
        );
        $this->assertTrue(
            method_exists($mapper4, 'customTestFunction'),
            'customTestFunction defined in CustomMapper class does not exist in mapper4 object'
        );

        $services->setAllowOverride(true);
        $services->setService('config', $configBackup);
        $services->setService('mongo', null);
        $services->setFactory('mongo', 'MongoObject\MapperFactory');
        $services->setAllowOverride(false);
    }
}
