<?php
namespace pyd\testkit\base;

use pyd\testkit\EventsDispatcher;

/**
 * Test case base class.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var boolean if set to false, a fresh Yii app will be created for each
     * test method of the test case.
     * If set to true, a single Yii app is created for all test methods of the
     * test case unless it is deleted by the tester. in this case a new Yii app
     * is automatically created.
     */
    public static $shareYiiApp = false;

    /**
     * @var boolean if set to false, each db table, @see dbTablesToLoad(), is
     * loaded before each test method of the test case.
     * If set to true, db is loaded once for all test methods of the test case
     * unless one or more tables are unloaded. In this case, unloaded tables
     * will be loaded again for the next test method. Note that unload must be
     * performed using the @see \pyd\testkit\fixtures\Db::unload() or
     * @see \pyd\testkit\fixtures\DbTable::unload() method. Deleting all rows
     * of a table using it's model 'delete' method won't cause de table to be
     * reloaded.
     */
    public static $shareDbFixture = false;

    /**
     * @var \pyd\testkit\fixtures\Manager
     */
    private static $fixturesManager;

    /**
     * @param \pyd\testkit\fixtures\Manager $fixturesManager
     */
    public static function setFixturesManager(\pyd\testkit\fixtures\Manager $fixturesManager)
    {
        self::$fixturesManager = $fixturesManager;
    }

    /**
     * Return config to create \pyd\testkit\fixtures\DbTable instances whose
     * tables must be populated with fixture data.
     */
    public static function dbTablesToLoad()
    {
        return [];
    }

    /**
     * @return \pyd\testkit\fixtures\Manager
     */
    public static function getFixturesManager()
    {
        return self::$fixturesManager;
    }

    public static function setUpBeforeClass()
    {
        self::$fixturesManager->getEventsDispatcher()->dispatch(EventsDispatcher::EVENT_SETUPBEFORECLASS, get_called_class());
    }

    public function setUp()
    {
        self::$fixturesManager->getEventsDispatcher()->dispatch(EventsDispatcher::EVENT_SETUP, $this);
    }

    public function tearDown()
    {
        self::$fixturesManager->getEventsDispatcher()->dispatch(EventsDispatcher::EVENT_TEARDOWN, $this);
    }

    public static function tearDownAfterClass()
    {
        $testCaseEnd = self::$fixturesManager->getInitialPID() === getmypid();
        self::$fixturesManager->getEventsDispatcher()->dispatch(EventsDispatcher::EVENT_TEARDOWNAFTERCLASS, get_called_class(), $testCaseEnd);
    }
}
