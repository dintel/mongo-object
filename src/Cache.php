<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2015 Dmitry Zbarski
 */
namespace MongoObject;

/**
 * Cache interface defines caching interface that Mapper relies on. Any class
 * implementing this interface can be used to provide caching mechanism to Mapper.
 */
interface Cache
{
    public function __construct(array $config = null);
    public function store($key, array $document);
    public function fetch($key);
    public function delete($key);
    public function clear();
}
