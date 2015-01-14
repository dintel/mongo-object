<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjectTest;

use MongoObject\Object;

class ObjectTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->testSchema =  [
            '_id' => ['type' => Object::TYPE_ID, 'null' => false],
            'login' => ['type' => Object::TYPE_STRING, 'null' => false],
            'type' => ['type' => Object::TYPE_STRING, 'null' => false],
            'name' => ['type' => Object::TYPE_STRING, 'null' => false],
            'email' => ['type' => Object::TYPE_STRING, 'null' => false],
            'password' => ['type' => Object::TYPE_STRING, 'null' => false, 'hidden' => true],
            'active' => ['type' => Object::TYPE_BOOL, 'null' => false],
        ];
        $this->defaults = [
            'active' => true,
        ];
    }

    public function testCreate()
    {
        $obj = new Object($this->testSchema, ['login' => 'login', 'type' => 'someType' ,'name' => 'someone', 'password' => 'secret'] + $this->defaults, $this->collection);
        $this->assertNull($obj->_id);
        $this->assertSame("login", $obj->login);
        $this->assertSame("someType", $obj->type);
        $this->assertSame("someone", $obj->name);
        $this->assertSame('', $obj->email);
        $this->assertTrue($obj->active);
        return $obj;
    }

    /**
     * @depends testCreate
     */
    public function testSave(Object $obj)
    {
        $this->assertTrue($obj->save());
        $this->assertSame("",$obj->email);
        $obj->email = "test@test.com";
        $this->assertTrue($obj->save());
        $this->assertSame("test@test.com",$obj->email);
        return $obj;
    }

    /**
     * @depends testSave
     */
    public function testDelete(Object $obj)
    {
        $this->assertTrue($obj->delete());
        $obj = new Object($this->testSchema, ['login' => 'login', 'type' => 'someType' ,'name' => 'someone', 'password' => 'secret'] + $this->defaults, $this->collection);
        $this->assertFalse($obj->delete());
    }

    /**
     * @expectedException MongoObject\Exception
     */
    public function testInvalidSchema()
    {
        $badSchema = $this->testSchema;
        $badSchema['badProp'] = null;
        $obj = new Object($badSchema, ['login' => 'login', 'type' => 'someType' ,'name' => 'someone', 'password' => 'secret'] + $this->defaults, $this->collection);
        echo $obj->badProp;
    }

    /**
     * @expectedException MongoObject\Exception
     */
    public function testInvalidType()
    {
        $badSchema = $this->testSchema;
        $badSchema['badProp'] = ['type' => 100];
        $obj = new Object($badSchema, ['login' => 'login', 'type' => 'someType' ,'name' => 'someone', 'password' => 'secret'] + $this->defaults, $this->collection);
        echo $obj->badProp;
    }
}
