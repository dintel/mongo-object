<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjectTest;

use MongoId;
use MongoDate;

class MapperTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->admin = $this->mapper->findObjectByProp('User', 'login', 'admin');
        $this->data = [
            'login' => 'testuser',
            'type' => User::TYPE_USER,
            'name' => 'Test user',
            'password' => hash('sha256', 'user'),
            'active' => false,
            'age' => 22.5,
            'created' => new MongoDate(),
            'manager' => null,
        ];
    }

    public function testFindObject()
    {
        $user = $this->mapper->findObject('User', $this->admin->_id);
        $this->assertInstanceOf('MongoObjectTest\User', $user);
        $this->assertEquals($this->admin->_id, $user->_id);

        $user = $this->mapper->findObject('User', (string) $this->admin->_id);
        $this->assertInstanceOf('MongoObjectTest\User', $user);
        $this->assertEquals($this->admin->_id, $user->_id);

        $user = $this->mapper->findObject('User', new MongoId());
        $this->assertNull($user);

        $user = $this->mapper->findObject('User', "invalid id");
        $this->assertNull($user);

        $user = $this->mapper->findObject('User', null);
        $this->assertNull($user);

        $user = $this->mapper->findObject('User', 12355);
        $this->assertNull($user);
    }

    /**
     * @depends testFindObject
     */
    public function testFindObjectByProp()
    {
        $user = $this->mapper->findObjectByProp('User', 'login', 'admin2');
        $this->assertInstanceOf('MongoObjectTest\User', $user);
        $this->assertEquals('admin2', $user->login);

        $user = $this->mapper->findObjectByProp('User', "_id", null);
        $this->assertNull($user);

        $user = $this->mapper->findObjectByProp('User', "_id", '11223');
        $this->assertNull($user);

        $user = $this->mapper->findObjectByProp('User', "nothing", 1);
        $this->assertNull($user);
    }

    /**
     * @depends testFindObjectByProp
     */
    public function testCountObjects()
    {
        $this->assertSame(0, $this->mapper->countObjects('User', ['type' => 100]));
        $this->assertSame(2, $this->mapper->countObjects('User', ['type' => User::TYPE_ADMIN]));
        $this->assertSame(1, $this->mapper->countObjects('User', ['type' => User::TYPE_GUEST]));
        $this->assertSame(3, $this->mapper->countObjects('User', []));
        $this->assertSame(false, $this->mapper->countObjects('UnkonwnType', ['type' => User::TYPE_ADMIN]));
        $this->assertSame(0, $this->mapper->countObjects('User', ['none' => 1]));
    }

    /**
     * @depends testCountObjects
     */
    public function testNewObject()
    {
        $user = $this->mapper->newObject('User', $this->data);
        $this->assertInstanceOf('MongoObjectTest\User', $user);
        $this->assertSame($this->data['login'], $user->login);
        $user = $this->mapper->newObject('WrongUser', $this->data);
        $this->assertNull($user);
    }

    /**
     * @depends testNewObject
     */
    public function testMagicGet()
    {
        $this->assertInstanceOf('MongoDB', $this->mapper->mongodb);
    }

    /**
     * @depends testMagicGet
     */
    public function testFetchObjects()
    {
        $users = $this->mapper->fetchObjects('User', ['type' => User::TYPE_ADMIN]);
        $this->assertSame(2, count($users));
        $this->assertInstanceOf('MongoObjectTest\User', $users[0]);
        $this->assertInstanceOf('MongoObjectTest\User', $users[1]);

        $users = $this->mapper->fetchObjects('User', ['type' => User::TYPE_ADMIN], ['login' => 1]);
        $this->assertSame(2, count($users));
        $this->assertInstanceOf('MongoObjectTest\User', $users[0]);
        $this->assertInstanceOf('MongoObjectTest\User', $users[1]);
        $this->assertSame('admin', $users[0]->login);
        $this->assertSame('admin2', $users[1]->login);

        $users = $this->mapper->fetchObjects('User', ['type' => User::TYPE_ADMIN], ['login' => 1], 1);
        $this->assertSame(1, count($users));
        $this->assertInstanceOf('MongoObjectTest\User', $users[0]);
        $this->assertSame('admin', $users[0]->login);

        $users = $this->mapper->fetchObjects('User', ['type' => User::TYPE_ADMIN], ['login' => 1], null, 1);
        $this->assertSame(1, count($users));
        $this->assertInstanceOf('MongoObjectTest\User', $users[0]);
        $this->assertSame('admin2', $users[0]->login);

        $users = $this->mapper->fetchObjects('User', ['type' => 100]);
        $this->assertSame(0, count($users));
    }

    /**
     * @depends testFetchObjects
     */
    public function testErrors()
    {
        $this->assertNull($this->mapper->findObject('UnknownClass', 1));
        $this->assertNull($this->mapper->findObjectByProp('UnknownClass', 'attr', 'val'));
        $this->assertNull($this->mapper->fetchObjects('UnknownClass'));
    }

    /**
     * @depends testErrors
     */
    public function testUpdate()
    {
        $result = $this->mapper->updateObjects('User', ['type' => User::TYPE_ADMIN], ['age' => 6]);
        $this->assertSame($result, 2);
        $users = $this->mapper->fetchObjects('User', ['age' => 6.0]);
        $this->assertSame(2, count($users));
        $this->assertSame(6.0, $users[0]->age);
        $this->assertSame(6.0, $users[1]->age);

        $result = $this->mapper->updateObjects('UnknownType', ['type' => User::TYPE_ADMIN], ['age' => 6]);
        $this->assertSame($result, false);
    }

    /**
     * @depends testUpdate
     */
    public function testDelete()
    {
        $result = $this->mapper->deleteObjects('UnknownType', ['type' => User::TYPE_ADMIN]);
        $this->assertSame($result, false);

        $result = $this->mapper->deleteObjects('User', ['age' => 6.0]);
        $this->assertSame($result, true);
    }
}
