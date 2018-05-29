<?php
namespace pyd\testkit\fixtures\db;

use pyd\testkit\Manager as Testkit;
use pyd\testkit\TestCase;
use yii\base\InvalidCallException;
use pyd\testkit\Tests;

/**
 * Manage db tables content according to the test case events.
 * 
 * @see TablesManager
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
     * Create Table instances required by the test case {@see \pyd\testkit\TestCase::dbTablesToLoad()}.
     *
     * @param string $testCaseClass class of the currently executed test case
     * @param boolean $testCaseStart event 'setUpBeforeClass' occurs when a new
     * test case is processed VS before test method in isolation
     */
    public function onSetUpBeforeClass($testCaseClass, $testCaseStart)
    {
        $this->testCaseShareDbFixture = $testCaseClass::$shareDbFixture;
        
        if (!empty($testCaseClass::dbTablesToLoad())) {
            $this->testCaseRequireDb = true;
            $this->collection->setTables($testCaseClass::dbTablesToLoad());
            
            // force unloading when a test case starts in case db was not properly
            // unloaded in a previous phpunit execution
            if ($testCaseStart) {
                $this->unload(true);
            }
        } else {
            $this->testCaseRequireDb = false;
        }
    }

    /**
     * Handle 'setUp' event.
     * 
     * Ensure required db tables are loaded with fixture data.
     * 
     * If the previous test method was executed in isolation, the db tables
     * state i.e. loaded|unloaded may be different of their corresponding
     * {@see Table::$isloaded} property in the current php process. To make sure
     * that tables state match {@see Table::$isloaded} instances property we have
     * to force unload i.e. all tables are unloaded - even empty ones - and their
     * {@see Table::$isloaded} properties are set to false.
     * 
     * If the {@see $testCaseShareDbFixture} property is true or the current test
     * method is executed in isolation, db tables need fresh/untouched content.
     * So db tables must be unloaded then reloaded.
     * 
     * In other cases, only unloaded tables will be loaded with fixture data. 
     *
     * @param \pyd\testkit\TestCase $testCase
     */
    public function onSetUp(TestCase $testCase)
    {
        if ($this->testCaseRequireDb) {
            
            if ($testCase->isInIsolation()) {
                $this->unload(true);
                Tests::$manager->getSharedData()->set('afterIsolatedTest', true);
            } else if (Tests::$manager->getSharedData()->get('afterIsolatedTest', false)) {
                $this->unload(true);
                Tests::$manager->getSharedData()->set('afterIsolatedTest', false);
            } else if (!$this->testCaseShareDbFixture) {
                $this->unload();
            }
            
            // this will load only 'unloaded' tables
            $this->load();
        }
    }

    /**
     * Handle 'tearDownAfterClass' event.
     *
     * Db tables must be unloaded at the end of a test case or after a test
     * method executed in isolation.
     * 
     * @param string $testCaseClass class of the currently executed test case
     * @param boolean $testCaseEnd event 'tearDownAfterClass' occurs when a
     * test case ends VS after a test method in isolation
     */
    public function onTearDownAfterClass($testCaseClass, $testCaseEnd)
    {
        if ($this->testCaseRequireDb) {
            if ($testCaseEnd) {
                $this->unload();
                $this->collection->clear();
            }
        }
    }

    /**
     * Store Table class names, which tables are loaded, in shared memory.
     */
    protected function saveInstancesLoadState()
    {
        $loadedTables = [];
        foreach ($this->collection->getAll() as $key => $table) {
            if ($table->getIsLoaded()) {
                $loadedTables[] = $key;
            }
        }
        Tests::$manager->getSharedData()->setLoadedDbTables($loadedTables);
    }

    /**
     * Refresh db tables status - loaded|unloaded - from shared memory.
     */
    protected function refreshInstancesLoadState()
    {
        $loadedTables = Tests::$manager->getSharedData()->getLoadedDbTables();
        foreach ($this->collection->getAll() as $key => $table) {
            $table->forceLoadState(in_array($key, $loadedTables));
        }
    }
}
