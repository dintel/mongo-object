<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2015 Dmitry Zbarski
 */
namespace MongoObject;

use MongoCollection;

/**
 * MongoObject interface must be implemented by all classes that derive from
 * Object if you use Mapper.
 */
interface MapperObject
{
    public function __construct(array $data, MongoCollection $collection);
    public static function getCollection();
}
