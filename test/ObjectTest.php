<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjectTest;

class ObjectTest extends AbstractTestCase
{
    private $cache;

    public function setUp()
    {
        $this->cache = new \MongoObject\NullCache(null);
        parent::setUp();
    }

    /**
     * @expectedException MongoObject\Exception
     */
    public function testInvalidSchema()
    {
        $user = new UserInvalidSchema([], $this->collection, $this->cache);
    }

    /**
     * @expectedException MongoObject\Exception
     */
    public function testInvalidType()
    {
        $user = new UserInvalidType([], $this->collection, $this->cache);
    }
}
