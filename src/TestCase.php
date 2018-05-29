<?php
namespace pyd\testkit;

use pyd\testkit\Tests;
use pyd\testkit\EventNotifier;

/**
 * Base class for test cases.
 * 
 * @see $shareYiiApp for Yii app - as a fixture - management policy
 * @see $shareDbFixture for db fixture management policy
 * By default the properties above are set to false i.e. for each test method,
 * a Yii app instance is created and required db tables are refreshed
 * 
 * @see dbTablesToLoad to define db tables that must be loaded with fixture data
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * One Yii app instance will be shared by all tests in this test case vs
     * an instance is created for each test.
     * 
     * Note: a fresh Yii app instance is always created for a test in isolation.
     * Note: destroying a Yii app in a test will force creation of a new instance
     * in the next test whatever the value of this property.
     * 
     * @see \pyd\testkit\fixtures\yiiApp\ManagerEventObserver
     */
    public static $shareYiiApp = false;
    
    /**
     * Db will be loaded with fixture data once at the beginning of the test
     * case vs it will be loaded before each test.
     * 
     * Note: required tables are always reloaded for a test in isolation.
     * Note: unloading a table in a test will force its reload for the next
     * test whatever the value of this property.
     * 
     * @see \pyd\testkit\fixtures\db\ManagerEventObserver
     */
    public static $shareDbFixture = false;
    
    /**
     * Manager for db fixture.
     * 
     * @var \pyd\testkit\fixtures\db\Manager
     */
    public $dbFixture;
    
    /**
     * Manager for Yii app instance fixture.
     * 
     * @var \pyd\testkit\fixtures\yiiApp\Manager
     */
    public $yiiApp;
    
    /**
     * List of db tables that must be loaded for this test case.
     * 
     * A table is defined as a {@see \pyd\testkit\fixtures\db\Table} class.
     * ```php
     * return [
     *      'users' => tests\fixtures\db\UsersFixture::className(),
     *      'orders' => [
     *          'class' => tests\fixtures\db\OrdersFixture::className(),
     *          'depends' => [...]
     * ];
     * ```
     * @see pyd\testkit\fixtures\db\TablesCollection for more infos about format,
     * dependencies...
     * 
     * @return array
     */
    public static function dbTablesToLoad()
    {
        return [];
    }
    
    /**
     * Trigger the 'setUpBeforeClass' event.
     */
    public static function setUpBeforeClass()
    {
        Testkit::$app->eventMediator->informObservers(new events\SetUpBeforeClass(get_called_class()));
    }
    
    /**
     * Trigger the 'setUp' event.
     */
    public function setUp()
    {
        Testkit::$app->eventMediator->informObservers(new events\SetUp($this));
    }
    
    /**
     * Trigger the 'tearDown' event.
     */
    public function tearDown()
    {
        Testkit::$app->eventMediator->informObservers(new events\TearDown($this));
    }
    
    /**
     * Trigger the 'tearDownAfterClass' event.
     */
    public static function tearDownAfterClass()
    {
        Testkit::$app->eventMediator->informObservers(new events\TearDownAfterClass(get_called_class()));
    }

    /**
     * Suspend test execution until tester press the ENTER key.
     *
     * Note: the terminal window must have focus for the key press to be
     * detected
     */
    public function pause()
    {
        if (trim(fgets(fopen("php://stdin","r"))) != chr(13)) return;
    }
}
