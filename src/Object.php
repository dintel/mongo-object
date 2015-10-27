<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2015 Dmitry Zbarski
 */
namespace MongoObject;

use MongoId;
use MongoDBRef;
use MongoDate;
use MongoCollection;
use JsonSerializable;
use ArrayObject;

/**
 * Object is a base class for constructing objects that are saved into Mongo
 * database.
 *
 * Simplest usage of this class is to derive your own class from this class and
 * define schema of object in your own constructor. For examples see examples
 * directory.
 *
 * This class implements JsonSerializable, so objects of this class can be sent
 * to JavaScript using AJAX relatively simply. When object is serialized to
 * JSON, it's '_id' property is renamed to 'id'.
 */
class Object implements JsonSerializable
{
    /**
     * MongoId field type
     */
    const TYPE_ID=0;

    /**
     * Bool field type
     */
    const TYPE_BOOL=1;

    /**
     * Int field type
     */
    const TYPE_INT=2;

    /**
     * Double field type
     */
    const TYPE_DOUBLE=3;

    /**
     * String field type
     */
    const TYPE_STRING=4;

    /**
     * Array field type
     */
    const TYPE_ARRAY=5;

    /**
     * MongoDate field type
     */
    const TYPE_DATE=6;

    /**
     * MongoDBRef field type
     */
    const TYPE_REFERENCE=7;

    /**
     * @var array holds schema that defines property types of this class
     */
    protected $_schema;

    /**
     * @var MongoCollection collection in which object is stored
     */
    protected $_collection;

    /**
     * Get absolute name of type, including it's namespace
     * @param string $type type name
     * @return string absolute type name
     */
    protected function getFullType($type)
    {
        if (strstr($type, '\\')) {
            return $type;
        }
        return substr(static::class, 0, strrpos(static::class, '\\')) . "\\{$type}";
    }

    /**
     * Constructor
     * @param array $schema schema that defines properties of object
     * @param array $data properties values
     * @param MongoCollection $collection Mongo collection in which object is stored
     */
    public function __construct(array $schema, array $data, MongoCollection $collection)
    {
        $this->_schema = $schema;
        $this->_collection = $collection;

        foreach ($this->_schema as $name => $desc) {
            if (!property_exists(static::class, $name)) {
                throw new Exception("Property {$name} is not defined in class " . static::class);
            }
            $this->$name = isset($data[$name]) ? $data[$name] : null;
            $this->convertProperty($name);
        }
    }

    /**
     * Check that property conforms to schema and convert it if needed
     * @param string $name name of property to check and convert if needed
     * @return null
     * @throws Exception if there is an error in schema or conversion of
     * property value can not be done
     */
    private function convertProperty($name)
    {
        if (isset($this->_schema[$name]['null']) && $this->_schema[$name]['null'] && $this->$name === null) {
            return;
        }

        switch ($this->_schema[$name]['type']) {
        case Object::TYPE_ID:
            if (!($this->$name instanceof MongoId) && $this->$name !== null) {
                $this->$name = new MongoId($this->$name);
            }
            break;
        case Object::TYPE_BOOL:
            if (!is_bool($this->$name)) {
                $this->$name = (bool) $this->$name;
            }
            break;
        case Object::TYPE_INT:
            if (!is_int($this->$name)) {
                $this->$name = (int) $this->$name;
            }
            break;
        case Object::TYPE_DOUBLE:
            if (!is_double($this->$name)) {
                $this->$name = (double) $this->$name;
            }
            break;
        case Object::TYPE_STRING:
            if (!is_string($this->$name)) {
                $this->$name = (string) $this->$name;
            }
            break;
        case Object::TYPE_ARRAY:
            if (!is_array($this->$name)) {
                $this->$name = array();
            }
            break;
        case Object::TYPE_DATE:
            if (!($this->$name instanceof MongoDate)) {
                $this->$name = new MongoDate($this->$name);
            }
            break;
        case Object::TYPE_REFERENCE:
            if (!MongoDBRef::isRef($this->$name)) {
                $this->$name = null;
            }
            break;
        default:
            throw new Exception("Property '{$name}' type is unknown ({$this->_schema[$name]['type']})");
        }
        return null;
    }

    /**
     * Get data of this object as array
     *
     * @return array associative array of data
     */
    protected function getData()
    {
        $data = [];
        foreach ($this->_schema as $name => $desc) {
            $data[$name] = $this->$name;
            if (isset($desc['updateDate']) && $desc['updateDate']) {
                $data[$name] = new MongoDate();
            }
        }
        if ($data['_id'] === null) {
            // @codeCoverageIgnoreStart
            unset($data['_id']);
            // @codeCoverageIgnoreEnd
        }
        return $data;
    }

    /**
     * Magic method that sets property value
     * @param string $name name of property to set
     * @param mixed $value value of property to assign
     * @return null
     * @throws Exception if property does not exist in schema or is hidden
     */
    public function __set($name, $value)
    {
        if (!isset($this->_schema[$name])) {
            throw new Exception("Property '{$name}' could not be set, does not exist in schema.");
        }
        if (isset($this->_schema[$name]['hidden']) && $this->_schema[$name]['hidden']) {
            throw new Exception("Property '{$name}' could not be set, it is hidden.");
        }

        $this->$name = $value;
        $this->convertProperty($name);
    }

    /**
     * Magic method that gets property value
     * @param string $name name of property to get
     * @return mixed value of property
     * @throws Exception if property does not exist in schema or is hidden
     */
    public function __get($name)
    {
        if (!isset($this->_schema[$name])) {
            throw new Exception("Property '{$name}' could not be get, does not exist in schema.");
        }
        if (isset($this->_schema[$name]['hidden']) && $this->_schema[$name]['hidden']) {
            throw new Exception("Property '{$name}' could not be get, it is hidden.");
        }

        return $this->$name;
    }

    /**
     * Magic method that checks whether property is defined
     * @param string $name name of property
     * @return bool true if defined, false otherwise
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Save object into Mongo collection
     * @return bool true on success, false on failure
     */
    public function save()
    {
        $data = $this->getData();
        if ($this->_collection->save($data, ["j" => true])['err'] === null) {
            $this->_id = $data['_id'];
            return true;
        }
        // @codeCoverageIgnoreStart
        return false;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Delete object from Mongo collection
     * @return bool true if object was removed, false otherwise
     */
    public function delete()
    {
        if (!$this->isNew()) {
            $this->_collection->remove(['_id' => $this->_id]);
            return true;
        }
        return false;
    }

    /**
     * Check whether this object was ever saved to Mongo collection
     * @return bool true if object was ever saved to Mongo, false otherwise
     */
    public function isNew()
    {
        return $this->_id === null;
    }

    /**
     * Get Mongo DBRef object pointing to this object
     * @return array representation of Mongo DBRef that references this object
     * or null if this object was never saved and is new
     */
    public function getDBRef()
    {
        return $this->isNew() ? null : MongoDBRef::create($this->_collection->getName(), $this->_id);
    }

    /**
     * Fetch object using MongoDBRef
     * @param string $typeName name of class of object to fetch (will be
     * preprended with $this->_modelsNamespace)
     * @param array $dbref array holding Mongo DBRef reference to object
     * @return mixed object or null if fetching failed
     */
    protected function fetchDBRef($typeName, $dbref)
    {
        $typeName = $this->getFullType($typeName);
        if (class_exists($typeName) && MongoDBRef::isRef($dbref)) {
            $collectionName = $typeName::getCollection();
            $collection = $this->_collection->db->$collectionName;
            $data = $this->_collection->getDBRef($dbref);
            if ($data === null) {
                return null;
            }
            return new $typeName($data, $collection);
        }
        return null;
    }

    /**
     * Reload data from Mongo
     * @return null
     */
    public function refresh()
    {
        if ($this->_id !== null) {
            $data = $this->_collection->findOne(['_id' => $this->_id]);
            foreach ($this->_schema as $name => $desc) {
                if (isset($data[$name])) {
                    $this->$name = $data[$name];
                }
            }
        }
    }

    /**
     * Get array of data for JSON serialization
     * @return array data to be exported to JSON
     */
    public function jsonSerialize()
    {
        $jsonData = $this->getData();
        if (isset($jsonData["_id"])) {
            $jsonData["id"] = (string)$jsonData["_id"];
            // @codeCoverageIgnoreStart
            unset($jsonData["_id"]);
            // @codeCoverageIgnoreEnd
        }
        foreach ($this->_schema as $name => $dsc) {
            if (@$dsc['hidden']) {
                // @codeCoverageIgnoreStart
                unset($jsonData[$name]);
                // @codeCoverageIgnoreEnd
            }
        }
        return $jsonData;
    }

    /**
     * Merge properties values from another array
     * @param array $data array of properties values
     * @return null
     */
    public function mergeData(array $data)
    {
        foreach ($data as $name => $val) {
            $this->$name = $val;
            $this->convertProperty($name);
        }
    }
}
