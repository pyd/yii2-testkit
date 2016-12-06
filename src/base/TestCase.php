<?php
namespace pyd\testkit\base;

use pyd\testkit\Events;

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
    protected $fixtures;
    /**
     * @var \pyd\testkit\fixtures\Db
     */
    protected $dbFixture;
    /**
     * @var \pyd\testkit\Manager
     */
    private static $testkit;

    /**
     * @param \pyd\testkit\Manager $testkit
     */
    public static function setTestkit(\pyd\testkit\Manager $testkit)
    {
        self::$testkit = $testkit;
    }

    /**
     * @return \pyd\testkit\Manager
     */
    public static function getTestkit()
    {
        return self::$testkit;
    }

    /**
     * @return array config to create @see \pyd\testkit\fixtures\DbTable
     * instances and populate their db tables with test data
     */
    public static function dbTablesToLoad()
    {
        return [];
    }


    public static function setUpBeforeClass()
    {
        $testCaseStart = !self::$testkit->getIsInIsolation();
        self::$testkit->getEvents()->trigger(Events::SETUPBEFORECLASS, get_called_class(), $testCaseStart);
    }

    public function setUp()
    {
        self::$testkit->getEvents()->trigger(Events::SETUP, $this);
        $this->fixtures = self::$testkit->getFixtures();
        $this->dbFixture = self::$testkit->getFixtures()->getDb();
    }

    public function tearDown()
    {
        self::$testkit->getEvents()->trigger(Events::TEARDOWN, $this);
    }

    public static function tearDownAfterClass()
    {
        $testCaseEnd = !self::$testkit->getIsInIsolation();
        self::$testkit->getEvents()->trigger(Events::TEARDOWNAFTERCLASS, get_called_class(), $testCaseEnd);
    }
}
