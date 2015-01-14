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
        $this->admin = $this->mapper->findObjectByProp('users', 'User', 'login', 'admin');
        $this->data = [
            'login' => 'testuser',
            'type' => User::TYPE_USER,
            'name' => 'Test user',
            'password' => hash('sha256','user'),
            'active' => false,
            'age' => 22.5,
            'created' => new MongoDate(),
            'manager' => null,
        ];
    }

    public function testFindObject()
    {
        $user = $this->mapper->findObject('users', 'User', $this->admin->_id);
        $this->assertInstanceOf('MongoObjectTest\User', $user);
        $this->assertEquals($this->admin->_id, $user->_id);

        $user = $this->mapper->findObject('users', 'User', (string) $this->admin->_id);
        $this->assertInstanceOf('MongoObjectTest\User', $user);
        $this->assertEquals($this->admin->_id, $user->_id);

        $user = $this->mapper->findObject('users', 'User', new MongoId());
        $this->assertNull($user);

        $user = $this->mapper->findObject('users', 'User', "invalid id");
        $this->assertNull($user);

        $user = $this->mapper->findObject('users', 'User', null);
        $this->assertNull($user);

        $user = $this->mapper->findObject('users', 'User', 12355);
        $this->assertNull($user);
    }

    public function testFindObjectByProp()
    {
        $user = $this->mapper->findObjectByProp('users', 'User', 'login', 'admin2');
        $this->assertInstanceOf('MongoObjectTest\User', $user);
        $this->assertEquals('admin2', $user->login);

        $user = $this->mapper->findObjectByProp('users', 'User', "_id", null);
        $this->assertNull($user);

        $user = $this->mapper->findObjectByProp('users', 'User', "_id", '11223');
        $this->assertNull($user);

        $user = $this->mapper->findObjectByProp('users', 'User', "nothing", 1);
        $this->assertNull($user);
    }

    public function testCountObjects()
    {
        $this->assertSame(0, $this->mapper->countObjects('users', ['type' => 100]));
        $this->assertSame(2, $this->mapper->countObjects('users', ['type' => User::TYPE_ADMIN]));
        $this->assertSame(1, $this->mapper->countObjects('users', ['type' => User::TYPE_GUEST]));
        $this->assertSame(3, $this->mapper->countObjects('users', []));
        $this->assertSame(0, $this->mapper->countObjects('none', ['type' => User::TYPE_ADMIN]));
        $this->assertSame(0, $this->mapper->countObjects('users', ['none' => 1]));
    }

    public function testNewObject()
    {
        $user = $this->mapper->newObject('users', 'User', $this->data);
        $this->assertInstanceOf('MongoObjectTest\User', $user);
        $this->assertSame($this->data['login'], $user->login);
        $user = $this->mapper->newObject('users', 'WrongUser', $this->data);
        $this->assertNull($user);
    }

    public function testFetchObjects()
    {
        $users = $this->mapper->fetchObjects('users', 'User', ['type' => User::TYPE_ADMIN]);
        $this->assertSame(2, count($users));
        $this->assertInstanceOf('MongoObjectTest\User', $users[0]);
        $this->assertInstanceOf('MongoObjectTest\User', $users[1]);

        $users = $this->mapper->fetchObjects('users', 'User', ['type' => User::TYPE_ADMIN], ['login' => 1]);
        $this->assertSame(2, count($users));
        $this->assertInstanceOf('MongoObjectTest\User', $users[0]);
        $this->assertInstanceOf('MongoObjectTest\User', $users[1]);
        $this->assertSame('admin', $users[0]->login);
        $this->assertSame('admin2', $users[1]->login);

        $users = $this->mapper->fetchObjects('users', 'User', ['type' => 100]);
        $this->assertSame(0, count($users));
    }
}
