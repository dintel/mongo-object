<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Dmitry Zbarski
 */

namespace MongoObjecttest;

use MongoClient;
use MongoCollection;
use MongoDB;
use PHPUnit_Framework_TestCase as TestCase;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @var string
     */
    const TEST_COLLECTION_NAME="users";

    /**
     * @var \MongoClient
     */
    protected $client;

    /**
     * @var \MongoDB
     */
    protected $db;

    /**
     * @var \MongoCollection
     */
    protected $collection;

    public function setUp()
    {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('Mongo extension is required to run tests');
        }

        $services = Bootstrap::getServiceManager();
        $config   = $services->get('config')['mongoObjectMapper'];

        $this->mongo      = new MongoClient($config['uri'], $config['options']);
        $this->db         = new MongoDB($this->mongo, $config['database']);
        $this->collection = new MongoCollection($this->db, self::TEST_COLLECTION_NAME);
        $this->mapper     = $services->get('mongo');
        $this->seedCollection();
    }

    public function tearDown()
    {
        if ($this->db !== null) {
            $this->db->drop();
        }
    }

    protected function seedCollection()
    {
        $this->collection->insert([
            'login' => 'admin',
            'type' => User::TYPE_ADMIN,
            'name' => 'Active admin user',
            'email' => 'admin@example.com',
            'password' => hash('sha256', 'admin'),
            'active' => true,
        ]);

        $this->collection->insert([
            'login' => 'guest',
            'type' => User::TYPE_GUEST,
            'name' => 'Inactive guest user',
            'email' => 'guest@example.com',
            'password' => hash('sha256', 'guest'),
            'active' => false,
        ]);

        $this->collection->insert([
            'login' => 'admin2',
            'type' => User::TYPE_ADMIN,
            'name' => 'Activeless admin user',
            'email' => 'admin2@example.com',
            'password' => hash('sha256', 'admin'),
        ]);
    }
}
