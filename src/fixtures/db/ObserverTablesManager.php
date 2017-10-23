<?php
namespace pyd\testkit\fixtures\db;

use pyd\testkit\Manager as Testkit;
use pyd\testkit\TestCase;
use yii\base\InvalidCallException;
use pyd\testkit\Tests;

/**
 * Manage db tables content according to the test case events.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ObserverTablesManager extends TablesManager
{
    /**
     * Db fixture can be shared in the current test case.
     * 
     * @see \pyd\testkit\TestCase::$shareDbFixture
     * 
     * @var boolean
     */
    protected $testCaseShareDbFixture;
    /**
     * Current test case require some db tables to be loaded with fixture.
     * 
     * @see \pyd\testkit\TestCase::dbTablesToLoad()
     * 
     * @var boolean
     */
    protected $testCaseRequireDb;

    /**
     * Handle 'setUpBeforeClass' event.
     * 
     * Create Table instances required by the test case.
     * @see \pyd\testkit\TestCase::dbTablesToLoad()
     *
     * @param string $testCaseClassName class of the currently executed test case
     */
    public function onSetUpBeforeClass($testCaseClassName)
    {
        $this->testCaseShareDbFixture = $testCaseClassName::$shareDbFixture;
        $dbTablesToLoad = $testCaseClassName::dbTablesToLoad();
        
        if (!empty($dbTablesToLoad)) {
            $this->testCaseRequireDb = true;
            $this->collection->createDbTableInstances($dbTablesToLoad);
        } else {
            $this->testCaseRequireDb = false;
        }
    }

    /**
     * Handle 'setUp' event.
     * 
     * Ensure that the db tables are loaded.
     * Force reload if the test method is executed in isolation.
     *
     * @param \pyd\testkit\TestCase $testCase
     */
    public function onSetUp(TestCase $testCase)
    {
        if ($this->testCaseRequireDb) {
            $this->refreshInstancesLoadState();
            if ($testCase->isInIsolation()) {
                $this->unload();
            }
            $this->load();
        }
    }

    /**
     * Handle 'tearDown' event.
     *
     * Unload db tables if fixture is not shared.
     * If the test method is executed in isolation, the db tables are unloaded
     * when the 'tearDownAfterClass' event occurs.
     */
    public function onTearDown(TestCase $testCase)
    {
        if ($this->testCaseRequireDb) {
            if (!$testCase->isInIsolation() && !$this->testCaseShareDbFixture) {
                $this->unload();
            }
            $this->saveInstancesLoadState();
        }
    }

    /**
     * Handle 'tearDownAfterClass' event.
     *
     * Db tables must be unloaded at the end of a test case or after a test
     * method executed in isolation.
     */
    public function onTearDownAfterClass()
    {
        if ($this->testCaseRequireDb) {
            $this->unload();
            $this->collection->clear();
        }
    }

    /**
     * Store Table class names, which tables are loaded, in shared memory.
     */
    protected function saveInstancesLoadState()
    {
        $loadedDbTableClassNames = [];
        foreach ($this->collection->getAll() as $table) {
            if ($table->getIsLoaded()) {
                $loadedDbTableClassNames[] = get_class($table);
            }
        }
        Tests::$manager->getSharedData()->setLoadedDbTables($loadedDbTableClassNames);
    }

    /**
     * Refresh db tables status - loaded|unloaded - from shared memory.
     */
    protected function refreshInstancesLoadState()
    {
        $loadedDbTableClassNames = Tests::$manager->getSharedData()->getLoadedDbTables();
        foreach ($this->collection->getAll() as $table) {
            $isloaded = in_array(get_class($table), $loadedDbTableClassNames);
            $table->forceLoadState($isloaded);
        }
    }
}
