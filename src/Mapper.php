<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2015 Dmitry Zbarski
 */
namespace MongoObject;

use MongoDB;
use MongoId;
use MongoDBRef;
use MongoException;

/**
 * Mapper class simplifies fetching of objects of MongoObject\Object class or
 * it's derivatives.
 */
class Mapper
{
    /**
     * @var MongoDB Mongo database
     */
    private $mongodb;

    /**
     * @var string Namespace where derivatives of Object are defined
     */
    private $modelsNamespace;

    /**
     * Constructor
     * @param MongoDB $mongodb Mongo database to work with
     * @param string|null $modelsNamespace optional namespace in which
     * derivatives of Object are defined
     */
    public function __construct(MongoDB $mongodb, $modelsNamespace = null)
    {
        $this->mongodb = $mongodb;
        $this->modelsNamespace = $modelsNamespace;
    }

    /**
     * Getter for all properties
     * @param string $name name of property to retrieve
     * @return mixed value of property
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Add models namespace to type, if it is not null
     * @param string $type type name
     * @return string type name prepended with models namespace if it is not null
     */
    protected function getFullType($type)
    {
        return $this->modelsNamespace === null ? $type : "{$this->modelsNamespace}\\{$type}";
    }

    /**
     * Find object by it's Mongo ID and return it
     * @param string $type Name of class of object that should be returned
     * @param mixed $id ID of document in Mongo collection that holds the object
     * @return mixed null if document not found, object of type $type otherwise
     */
    public function findObject($type, $id)
    {
        $type = $this->getFullType($type);
        if (class_exists($type)) {
            $table = $type::getCollection();
            if (!($id instanceof MongoId) && $id !== null) {
                try {
                    $id = new MongoId($id);
                } catch (MongoException $e) {
                    $id = null;
                }
            }
            $data = $this->mongodb->$table->findOne(['_id' => $id]);
            if ($data === null) {
                return null;
            }
            return new $type($data, $this->mongodb->$table, $this->modelsNamespace);
        }
        return null;
    }

    /**
     * Find object by it's property value and return it
     * @param string $type Name of class of object that should be returned
     * @param string $name Name of property to match
     * @param mixed $value Value of property to match
     * @return mixed null if document not found, object of type $type otherwise
     */
    public function findObjectByProp($type, $name, $value)
    {
        $type = $this->getFullType($type);
        if (class_exists($type)) {
            $table = $type::getCollection();
            $data = $this->mongodb->$table->findOne([$name => $value]);
            if ($data === null) {
                return null;
            }
            return new $type($data, $this->mongodb->$table, $this->modelsNamespace);
        }
        return null;
    }

    /**
     * Find all objects using Mongo selector and return them optionally ordered
     * @param string $type Name of class of object that should be returned
     * @param array $selector Mongo query used to match documents holding objects
     * @param array|null $order array if properties by which to sort (if value
     * is 1 sorted ascending, if -1 sorted descending)
     * @return array array of object matching selector ordered by $order, if specified
     */
    public function fetchObjects($type, array $selector = [], array $order = null)
    {
        $type = $this->getFullType($type);
        if (class_exists($type)) {
            $table = $type::getCollection();
            $cursor = $this->mongodb->$table->find($selector);
            if ($order) {
                $cursor->sort($order);
            }
            $result = [];
            foreach ($cursor as $data) {
                $obj = new $type($data, $this->mongodb->$table, $this->modelsNamespace);
                $result[] = $obj;
            }
            return $result;
        }
        return null;
    }

    /**
     * Count objects in collection matching Mongo query
     * @param string $table name of Mongo collection in which objects are
     * counted
     * @param array $query Mongo select query, only objects matching it are counted
     * @return int number of objects matching query
     */
    public function countObjects($table, $query = array())
    {
        $cursor = $this->mongodb->$table->find($query);
        return $cursor->count();
    }

    /**
     * Construct new object
     * @param string $type Name of class of object that should be constructed
     * @param array $data initial values of properties of new object
     * @return mixed new object of class $type or null of class $type does not exist
     */
    public function newObject($type, array $data = array())
    {
        $type = $this->getFullType($type);
        if (class_exists($type)) {
            $table = $type::getCollection();
            return new $type($data, $this->mongodb->$table, $this->modelsNamespace);
        }
        return null;
    }
}
