<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjectTest;

class ObjectTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @expectedException MongoObject\Exception
     */
    public function testInvalidSchema()
    {
        $user = new UserInvalidSchema([], $this->collection);
    }

    /**
     * @expectedException MongoObject\Exception
     */
    public function testInvalidType()
    {
        $user = new UserInvalidType([], $this->collection);
    }
}
