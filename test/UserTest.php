<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjectTest;

use MongoDate;
use MongoDBRef;

class UserTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
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

    public function testCreate()
    {
        $user = new User($this->data, $this->collection);
        $this->assertNull($user->_id);
        $this->assertSame($this->data['login'], $user->login);
        $this->assertSame($this->data['type'], $user->type);
        $this->assertSame($this->data['name'], $user->name);
        $this->assertSame($this->data['active'], $user->active);
        $this->assertSame([], $user->groups);
        $this->assertSame($this->data['age'], $user->age);
        $this->assertSame($this->data['created'], $user->created);
        $this->assertSame($this->data['manager'], $user->manager);
        $this->assertSame($this->data['active'], $user->active);
        $this->assertTrue($user->checkPassword("user"));
        $this->assertFalse($user->checkPassword('wrond password'));
        $this->assertTrue(isset($user->name));
        $this->assertFalse(isset($user->nothing));
        return $user;
    }

    /**
     * @depends testCreate
     */
    public function testPropType(User $user)
    {
        $user->_id = null;
        $user->login = null;
        $user->type = null;
        $user->name = null;
        $user->active = null;
        $user->groups = null;
        $user->age = null;
        $user->created = null;
        $user->manager = null;
        $this->assertNull($user->_id);
        $this->assertInternalType('string', $user->login);
        $this->assertInternalType('integer', $user->type);
        $this->assertInternalType('string', $user->name);
        $this->assertInternalType('boolean', $user->active);
        $this->assertInternalType('array', $user->groups);
        $this->assertInternalType('double', $user->age);
        $this->assertInstanceOf('MongoDate', $user->created);
        $this->assertNull($user->manager);
        $user->manager = "reference";
        $this->assertNull($user->manager);
        $user->manager = ['$ref' => 'something', '$id' => 'some id'];
        $this->assertInternalType('array', $user->manager);
    }

    /**
     * @depends testCreate
     */
    public function testMergeData(User $user)
    {
        $user->mergeData([
            '_id' => null,
            'age' => "none",
            'unknown' => 'property',
            'should' => 'be ignored',
        ]);
        $this->assertNull($user->_id);
        $this->assertInternalType('string', $user->login);
        $this->assertInternalType('integer', $user->type);
        $this->assertInternalType('string', $user->name);
        $this->assertInternalType('boolean', $user->active);
        $this->assertInternalType('array', $user->groups);
        $this->assertInternalType('double', $user->age);
        $this->assertInstanceOf('MongoDate', $user->created);

    }

    /**
     * @depends testCreate
     * @expectedException MongoObject\Exception
     */
    public function testHiddenPropRead(User $user)
    {
        echo $user->password;
        return $user;
    }

    /**
     * @depends testCreate
     * @expectedException MongoObject\Exception
     */
    public function testHiddenPropWrite(User $user)
    {
        $user->password = hash('sha256', 'password');
        return $user;
    }

    /**
     * @depends testCreate
     */
    public function testSave(User $user)
    {
        $this->assertTrue($user->isNew());
        $this->assertNull($user->getDBRef());
        $this->assertTrue($user->save());
        $this->assertFalse($user->isNew());
        $this->assertInternalType('array', $user->getDBRef());
        $this->assertInstanceOf('MongoId', $user->_id);
        $id = $user->_id;
        $user->name = "Changed name";
        $user->_id = (string) $id;
        $this->assertTrue($user->save());
        $this->assertSame("Changed name", $user->name);
        $this->assertEquals($id, $user->_id);
        return $user;
    }

    /**
     * @depends testCreate
     */
    public function testRefreshAndMergeData(User $user)
    {
        $user->save();
        $name = $user->name;
        $user->mergeData(['name' => 'some other name']);
        $this->assertSame('some other name', $user->name);
        $user->refresh();
        $this->assertSame($name, $user->name);
    }

    /**
     * @depends testSave
     */
    public function testDelete(User $user)
    {
        $this->assertTrue($user->delete());
        $user = new User($this->data, $this->collection);
        $this->assertFalse($user->delete());
    }

    public function testJson()
    {
        $user = $this->mapper->findObjectByProp('User', 'login', 'guest');
        $json = json_encode($user);
        $arr = json_decode($json, true);
        $this->assertInternalType('string', $json);
        $this->assertInternalType('array', $arr);
        $this->assertArrayNotHasKey('_id', $arr);
        $this->assertArrayHasKey('id', $arr);
        $user->_id = null;
        $json = json_encode($user);
        $arr = json_decode($json, true);
        $this->assertInternalType('string', $json);
        $this->assertInternalType('array', $arr);
        $this->assertArrayNotHasKey('_id', $arr);
        $this->assertArrayNotHasKey('id', $arr);
    }

    /**
     * @depends testCreate
     */
    public function testFetchDBRef(User $user)
    {
        $user->manager = null;
        $user->save();
        $this->assertNull($user->getManager());
        $this->assertNull($user->manager);
        $user->manager = $user->getDBRef();
        $user->save();
        $manager = $user->getManager();
        $this->assertTrue(MongoDBRef::isRef($user->manager));
        $this->assertInstanceOf('MongoObjectTest\User', $manager);
        $this->assertEquals($user->manager, $manager->manager);
        $manager = $user->getManager2();
        $this->assertInstanceOf('MongoObjectTest\User', $manager);
        $this->assertEquals($user->manager, $manager->manager);
        $this->assertNull($user->getManagerBroken1());
        $this->assertNull($user->getManagerBroken2());
        $admin2 = $this->mapper->findObjectByProp('User', 'login', 'admin2');
        $user->manager = $admin2->getDBRef();
        $user->save();
        $admin2->delete();
        $this->assertNull($user->getManager());
    }

    public function testRefreshOnIncompleteData()
    {
        $user = $this->mapper->findObjectByProp('User', 'login', 'admin');
        $user->refresh();
        $this->assertNull($user->manager);
    }

    /**
     * @depends testCreate
     * @expectedException MongoObject\Exception
     */
    public function testUndefinedPropWrite(User $user)
    {
        $user->undefined = true;
    }

    /**
     * @depends testCreate
     * @expectedException MongoObject\Exception
     */
    public function testUndefinedPropRead(User $user)
    {
        $test = $user->undefined;
    }
}
