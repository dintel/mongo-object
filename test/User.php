<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjectTest;

use MongoObject\Cache;
use MongoObject\Object;
use MongoObject\MapperObject;
use MongoCollection;
use MongoDate;

class User extends Object implements MapperObject
{
    const TYPE_ADMIN=0;
    const TYPE_USER=1;
    const TYPE_GUEST=2;

    protected $_id;
    protected $login;
    protected $type;
    protected $name;
    protected $password;
    protected $active;
    protected $groups;
    protected $age;
    protected $created;
    protected $manager;
    protected $modified;

    public function __construct(array $data, MongoCollection $collection, Cache $cache)
    {
        $schema = [
            '_id' => ['type' => Object::TYPE_ID, 'null' => false],
            'login' => ['type' => Object::TYPE_STRING, 'null' => false],
            'type' => ['type' => Object::TYPE_INT, 'null' => false],
            'name' => ['type' => Object::TYPE_STRING, 'null' => false],
            'password' => ['type' => Object::TYPE_STRING, 'null' => false, 'hidden' => true],
            'active' => ['type' => Object::TYPE_BOOL, 'null' => false],
            'groups' => ['type' => Object::TYPE_ARRAY, 'null' => false],
            'age' => ['type' => Object::TYPE_DOUBLE, 'null' => false],
            'created' => ['type' => Object::TYPE_DATE, 'null' => false],
            'manager' => ['type' => Object::TYPE_REFERENCE, 'null' => true],
            'modified' => ['type' => Object::TYPE_DATE, 'null' => false, 'updateDate' => true],
        ];
        $defaults = [
            'active' => true,
            'groups' => [],
            'created' => new MongoDate(),
            'modified' => new MongoDate(),
        ];
        parent::__construct($schema, $data + $defaults, $collection, $cache);
    }

    public function checkPassword($password)
    {
        return hash('sha256', $password) === $this->password;
    }

    public function getManager()
    {
        return $this->fetchDBRef('User', $this->manager);
    }

    public function getManager2()
    {
        return $this->fetchDBRef('MongoObjectTest\\User', $this->manager);
    }

    public function getManagerBroken1()
    {
        return $this->fetchDBRef('UnknownClass', $this->manager);
    }

    public function getManagerBroken2()
    {
        return $this->fetchDBRef('User', 'abracadbra');
    }

    public static function getCollection()
    {
        return 'users';
    }
}
