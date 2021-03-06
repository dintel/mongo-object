<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjectTest;

use MongoObject\Cache;
use MongoObject\Object;
use MongoCollection;
use MongoDate;

class UserInvalidType extends Object
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

    public function __construct(array $data, MongoCollection $collection, Cache $cache)
    {
        $schema = [
            '_id' => ['type' => Object::TYPE_ID, 'null' => false],
            'login' => ['type' => Object::TYPE_STRING, 'null' => false],
            'type' => ['type' => 111, 'null' => false],
            'name' => ['type' => Object::TYPE_STRING, 'null' => false],
            'password' => ['type' => Object::TYPE_STRING, 'null' => false, 'hidden' => true],
            'active' => ['type' => Object::TYPE_BOOL, 'null' => false],
            'groups' => ['type' => Object::TYPE_ARRAY, 'null' => false],
            'age' => ['type' => Object::TYPE_DOUBLE, 'null' => false],
            'created' => ['type' => Object::TYPE_DATE, 'null' => false],
            'manager' => ['type' => Object::TYPE_REFERENCE, 'null' => true],
        ];
        $defaults = [
            'active' => true,
            'groups' => [],
            'created' => new MongoDate(),
        ];
        parent::__construct($schema, $data + $defaults, $collection, $cache);
    }

    public function checkPassword($password)
    {
        return hash('sha256', $password) === $this->_data['password'];
    }

    public function getManager()
    {
        return $this->fetchDBRef('users', 'User', $this->manager);
    }

    public static function getCollection()
    {
        return 'users';
    }
}
