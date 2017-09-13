<?php
namespace pyd\testkit;

use pyd\testkit\Tests;
use pyd\testkit\EventNotifier;

/**
 * Base class for test case.
 * 
 * The default setting is 'isolation' oriented. The required db tables will be
 * loaded with fresh data and a new Yii app instance created for each test
 * method.
 * Check the @see $shareYiiApp and mostly the @see $shareDbFixture properties to
 * increase the testing speed.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    const SETUP_BEFORE_CLASS = 'setUpBeforeClass';
    const BEFORE_CLASS = 'beforeClass';
    const SETUP = 'setUp';
    const TEAR_DOWN = 'tearDown';
    const AFTER_CLASS = 'afterClass';
    const TEARDOWN_AFTER_CLASS = 'tearDownAfterClass';
    
    /**
     * Share the same Yii app instance between all test methods in this test
     * case.
     * 
     * If a test method is executed in isolation, a new app instance is available
     * whatever the value of this property.
     * 
     * If set to true, it is still possible to destroy a Yii app in a test method
     * to ensure that a new instance is created for the next test method(s).
     */
    public $shareYiiApp = false;
    /**
     * Load the db fixture once at the beginning of the test case vs load it before
     * each test method.
     * 
     * If a test method is executed in isolation, required tables will be reloaded
     * whatever the value of this property.
     * 
     * If set to true, it is still possible to unload one|some|all tables in a
     * test method to ensure that one|some|all tables are populated with fresh
     * data before the next test case.
     */
    public $shareDbFixture = false;
    /**
     * Manager for db fixture.
     * 
     * @var \pyd\testkit\fixtures\db\TablesManager
     */
    public $dbFixture;
    /**
     * Manager for yii app instance fixture.
     * 
     * @var \pyd\testkit\fixtures\YiiAppManager
     */
    public $yiiApp;
    /**
     * Required db fixture.
     * 
     * @return array config to create @see \pyd\testkit\fixtures\DbTable
     * instances and populate their db tables with test data
     */
    public static function dbTablesToLoad()
    {
        return [];
    }

    public static function setUpBeforeClass()
    {
        Tests::$manager->onSetUpBeforeClass(get_called_class());
    }

    public function setUp()
    {
        Tests::$manager->onSetUp($this);
    }

    public function tearDown()
    {
        Tests::$manager->onTearDown($this);
    }

    public static function tearDownAfterClass()
    {
        Tests::$manager->onTearDownAfterClass(get_called_class());
    }

    /**
     * Suspend test execution until tester press the ENTER key.
     *
     * @warning the terminal window must have when pressing enter key
     */
    public function pause()
    {
        if (trim(fgets(fopen("php://stdin","r"))) != chr(13)) return;
    }
}
