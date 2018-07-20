<?php
namespace pyd\testkit\fixtures\db;

use pyd\testkit\TestCase;
use yii\base\InvalidCallException;

/**
 * Manage db fixture by observing test case events.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ManagerEventObserver extends Manager implements \pyd\testkit\events\Observer
{
    use \pyd\testkit\events\ObserverEventHandler;
    
    /**
     * Does the current test case require db table(s) to be loaded with fixture?
     * 
     * @see \pyd\testkit\TestCase::dbTablesToLoad
     * @var boolean
     */
    protected $dbRequired;
    
    /**
     * Should the db fixture been shared between tests in the current test case.
     * 
     * @see \pyd\testkit\TestCase::$shareDbFixture
     * @var boolean
     */
    protected $shareFixture;

    /**
     * Handle the 'setUpBeforeClass' event.
     * 
     * Create Table instances required by the test case.
     * @see \pyd\testkit\TestCase::dbTablesToLoad
     *
     * @param \pyd\testkit\events\SetUpBeforeClass $event
     */
    protected function onSetUpBeforeClass(\pyd\testkit\events\SetUpBeforeClass $event)
    {
        $testCaseClass = $event->getTestCaseClass();
        $this->shareFixture = $testCaseClass::$shareDbFixture;
        $this->dbRequired = !empty($testCaseClass::dbTablesToLoad());
        
        if ($this->dbRequired) {
            $this->collection->setTables($testCaseClass::dbTablesToLoad());
        }
    }

    /**
     * Handle the 'setUp' event.
     * 
     * Ensure that required tables are loaded for the test.
     * Ensure that required tables are loaded with fresh data if fixture is not
     * meant to be shared {@see $shareFixture} by tests or if current test is
     * executed in isolation.
     *
     * @param \pyd\testkit\TestCase $testCase
     */
    protected function onSetUp(\pyd\testkit\events\SetUp $event)
    {
        $event->getTestCase()->dbFixture = $this;
        
        if ($this->dbRequired) {
            
            // goals:
            // - required db tables must contain fixture data;
            // - required db tables must be refreshed if current test is in isolation;
            // - refresh Tables::$isLoaded state if last test was in isolation;
            
            // if fixture is not shared:
            // - required tables were unloaded in onTearDown;
            // - no shared data variable was set;
            if (!$this->shareFixture) {
                $this->load();
            // if fixture is shared:
            // - if test was executed in isolation (see shared data variable), just
            //   remove this variable and update Table::$isLoaded state;
            // - if test was not executed in isolation, db needs to be refreshed
            //   if current test is in isolation
            } else {
               
                if (\pyd\testkit\Testkit::$app->sharedData->get('previousTestInIsolation', false)) {
                    
                    foreach ($this->getTables() as $table) {
                        $table->forceLoadedState(false);
                    }
                    \pyd\testkit\Testkit::$app->sharedData->remove('previousTestInIsolation');
                    
                } else if ($event->getTestIsInIsolation()) {
                    $this->unload();
                }
                $this->load();
            }
        }
    }
    
    /**
     * Handle the 'tearDown' event.
     * 
     * Unload required db tables if fixture is not meant to be shared
     * {@see $shareFixture} or if test is executed in isolation. This ensures
     * that they will be loaded with fresh data before the next test {@see onSetUp}.
     * 
     * @param \pyd\testkit\events\TearDown $event
     */
    protected function onTearDown(\pyd\testkit\events\TearDown $event)
    {
        if ($this->dbRequired) {
            if (!$this->shareFixture) {
                $this->unload();
            } else if ($event->getTestIsInIsolation()) {
                $this->unload();
                \pyd\testkit\Testkit::$app->sharedData->set('previousTestInIsolation', true);
            }
        }
    }

    /**
     * Handle the 'testCaseEnd' event.
     * 
     * Make sure that required tables are empty.
     * 
     * @param \pyd\testkit\events\EndTestCase $event
     */
    protected function onEndTestCase(\pyd\testkit\events\EndTestCase $event)
    {
        if ($this->dbRequired) {
            // unload is done in {@see onTearDown} is db fixture is not shared
            // or last test was in isolation
            if ($this->shareFixture) {
                if (false === \pyd\testkit\Testkit::$app->sharedData->get('previousTestInIsolation', false)) {
                    $this->unload();
                } else {
                    \pyd\testkit\Testkit::$app->sharedData->remove('previousTestInIsolation');
                }
            }
            $this->collection->clear();
        }

        $this->shareFixture = null;
        $this->dbRequired = null;
    }
}
