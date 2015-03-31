<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
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
 * to JavaScript using AJAX relatively simply.
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
     * @var array holds schema that defines properties of this class
     */
    protected $_schema;

    /**
     * @var array holds actual values of properties
     */
    protected $_data;

    /**
     * @var MongoCollection collection in which object is stored
     */
    protected $_collection;

    /**
     * @var string|null optional namespace in which subclasses are defined
     */
    protected $_modelsNamespace;

    /**
     * Add models namespace to type, if it is not null
     * @param string $type type name
     * @return string type name prepended with models namespace if it is not null
     */
    protected function getFullType($type)
    {
        return $this->_modelsNamespace === null ? $type : "{$this->_modelsNamespace}\\{$type}";
    }

    /**
     * Constructor
     * @param array $schema schema that defines properties of object
     * @param array $data properties values
     * @param MongoCollection $collection Mongo collection in which object is stored
     * @param string|null $modelsNamespace Optional namespace name where
     * MongoObject classes are defined
     */
    public function __construct(array $schema, array $data, MongoCollection $collection, $modelsNamespace = null)
    {
        $this->_schema = $schema;
        $this->_data = $data;
        $this->_collection = $collection;
        $this->_modelsNamespace = $modelsNamespace;

        foreach ($this->_schema as $name => $desc) {
            if (!isset($this->_data[$name])) {
                $this->_data[$name] = null;
            }
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
        if (isset($this->_schema[$name]['null']) && $this->_schema[$name]['null'] && $this->_data[$name] === null)
            return;

        switch ($this->_schema[$name]['type']) {
        case Object::TYPE_ID:
            if(!($this->_data[$name] instanceof MongoId) && $this->_data[$name] !== null)
                $this->_data[$name] = new MongoId($this->_data[$name]);
            break;
        case Object::TYPE_BOOL:
            if (!is_bool($this->_data[$name]))
                $this->_data[$name] = (bool) $this->_data[$name];
            break;
        case Object::TYPE_INT:
            if (!is_int($this->_data[$name]))
                $this->_data[$name] = (int) $this->_data[$name];
            break;
        case Object::TYPE_DOUBLE:
            if (!is_double($this->_data[$name]))
                $this->_data[$name] = (double) $this->_data[$name];
            break;
        case Object::TYPE_STRING:
            if (!is_string($this->_data[$name]))
                $this->_data[$name] = (string) $this->_data[$name];
            break;
        case Object::TYPE_ARRAY:
            if (!is_array($this->_data[$name]))
                $this->_data[$name] = array();
            break;
        case Object::TYPE_DATE:
            if (!($this->_data[$name] instanceof MongoDate))
                $this->_data[$name] = new MongoDate($this->_data[$name]);
            break;
        case Object::TYPE_REFERENCE:
            if (!MongoDBRef::isRef($this->_data[$name]))
                $this->_data[$name] = null;
            break;
        default:
            throw new Exception("Property '{$name}' type is unknown ({$this->_schema[$name]['type']})");
        }
        return null;
    }

    /**
     * Magic method that checks if property exist
     * @param string $name name of property
     * @return bool true if property is defined in schema, false otherwise
     */
    public function __isset($name)
    {
        return isset($this->_data[$name]);
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
        if (!isset($this->_schema[$name]))
            throw new Exception("Property '{$name}' could not be set, does not exist in schema.");
        if (isset($this->_schema[$name]['hidden']) && $this->_schema[$name]['hidden'])
            throw new Exception("Property '{$name}' could not be set, it is hidden.");

        $this->_data[$name] = $value;
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
        if (!isset($this->_schema[$name]))
            throw new Exception("Property '{$name}' could not be get, does not exist in schema.");
        if (isset($this->_schema[$name]['hidden']) && $this->_schema[$name]['hidden'])
            throw new Exception("Property '{$name}' could not be get, it is hidden.");

        return $this->_data[$name];
    }

    /**
     * Save object into Mongo collection
     * @return bool true on success, false on failure
     */
    public function save()
    {
        if ($this->_data['_id'] === null)
            unset($this->_data['_id']);
        if (isset($this->_data['modified']))
            $this->_data['modified'] = new MongoDate();
        file_put_contents("/tmp/dimatest", var_export($this->_data, true));
        $this->_collection->save($this->_data, ["j" => true]);
        return true;
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
     * Fetch object using Mongo DBRef
     * @param string $collectionName name of collection from which object should
     * be fetched
     * @param string $typeName name of class of object to fetch (will be
     * preprended with $this->_modelsNamespace)
     * @param array $dbref array holding Mongo DBRef reference to object
     * @return mixed object
     */
    protected function fetchDBRef($collectionName, $typeName, $dbref)
    {
    	$typeName = $this->getFullType($typeName);
        $collection = $this->_collection->db->$collectionName;
        if ($dbref === null) {
        	return null;
        }
        $data = $this->_collection->getDBRef($dbref);
        if ($data === null) {
        	return null;
        }
        return new $typeName($data, $collection, $this->_modelsNamespace);
    }

    /**
     * Reload data from Mongo
     * @return null
     */
    public function refresh()
    {
        if ($this->_data['_id'] !== null) {
            $this->_data = $this->_collection->findOne(['_id' => $this->_data['_id']]);
            foreach ($this->_schema as $name => $desc) {
                if (!isset($this->_data[$name])) {
                    $this->_data[$name] = null;
                }
                $this->convertProperty($name);
            }
        }
    }

    /**
     * Get array of data for JSON serialization
     * @return array data to be exported to JSON
     */
    public function jsonSerialize()
    {
        $jsonData = $this->_data;
        if (isset($jsonData["_id"])) {
            $jsonData["id"] = (string)$jsonData["_id"];
            unset($jsonData["_id"]);
        }
        foreach ($this->_schema as $name => $dsc) {
            if (@$dsc['hidden']) {
                unset($jsonData[$name]);
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
        $this->_data = $data + $this->_data;
    }
}
