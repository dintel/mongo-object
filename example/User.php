<?php
/**
 * @author Dmitry Zbarski <dmitry.zbarski@gmail.com>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */
namespace MongoObjectExample;

use MongoObject\Object;
use MongoCollection;
use MongoDate;
use MongoObject\MapperObject;

class User extends Object implements MapperObject
{
    public function __construct(array $data, MongoCollection $collection)
    {
        $schema = [
            '_id' => ['type' => Object::TYPE_ID, 'null' => false],
            'login' => ['type' => Object::TYPE_STRING, 'null' => false],
            'type' => ['type' => Object::TYPE_STRING, 'null' => false],
            'name' => ['type' => Object::TYPE_STRING, 'null' => false],
            'email' => ['type' => Object::TYPE_STRING, 'null' => false],
            'password' => ['type' => Object::TYPE_STRING, 'null' => false, 'hidden' => true],
            'active' => ['type' => Object::TYPE_BOOL, 'null' => false],
            'created' => ['type' => Object::TYPE_DATE, 'null' => false],
        ];
        $defaults = [
            'active' => true,
            'created' => new MongoDate(),
        ];
        parent::__construct($schema, $data + $defaults, $collection);
    }

    public function getCollection() {
        return "users";
    }

    public function checkPassword($password)
    {
        return hash('sha256', $password) === $this->_data['password'];
    }
}
