<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2015 Dmitry Zbarski
 */
namespace MongoObject;

/**
 * Null caching - does not really cache
 */
class NullCache implements Cache
{
    public function __construct(array $config = null)
    {
    }

    public function store($key, array $document)
    {
    }

    public function fetch($key)
    {
        return null;
    }

    public function delete($key)
    {
    }

    public function clear()
    {
    }
}
