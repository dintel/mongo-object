<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2015 Dmitry Zbarski
 */
namespace MongoObject;

/**
 * Caching for Mapper using Zend DataCache
 */
class ZendDataCacheShm implements Cache
{
    public function __construct(array $config = null)
    {
        if (!function_exists("zend_shm_cache_store")) {
            throw new \Exception("Missing Zend Data Cache PHP extension");
        }
    }

    public function store($key, array $document)
    {
        zend_shm_cache_store("MongoObject::{$key}", $document);
    }

    public function fetch($key)
    {
        $data = zend_shm_cache_fetch("MongoObject::{$key}");
        if ($data === false) {
            $data = null;
        }
        return $data;
    }

    public function delete($key)
    {
        zend_shm_cache_delete("MongoObject::{$key}");
    }

    public function clear()
    {
        zend_shm_cache_clear("MongoObject");
    }
}
