<?php
namespace pyd\testkit;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Manage a test session.
 * 
 * Its main goal is to manage other objects involved in the testing process -
 * especially fixtures managers and tools.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TestsManager extends \yii\base\Object
{
    /**
     * Test case events notifier.
     * 
     * @var \pyd\testkit\EventNotifier
     */
    protected $eventNotifier;
    /**
     * @var \pyd\testkit\fixtures\yiiApp\ObserverAppManager 
     */
    protected $yiiApp;
    /**
     * @var \pyd\testkit\fixtures\db\TestCaseTablesManager
     */
    protected $dbFixture;
    /**
     * @var \pyd\testkit\FileSharedData
     */
    protected $sharedData;
    /**
     * @var \pyd\testkit\web\ObserverDriverManager
     */
    protected $webDriverManager;
    
    /**
     * @param array $config
     */
    public static function run(array $config)
    {
        \pyd\testkit\Tests::$manager = new self($config);
    }
    
    public function init()
    {
        $this->registerObservers();
    }
    
    /**
     * @param string|array|callable $type the object type.
     */
    public function setEventNotifier($type)
    {
        $this->eventNotifier = Yii::createObject($type);
    }
    
    /**
     * @param string|array|callable $type the object type.
     */
    public function setYiiApp
            ($type)
    {
        $this->yiiApp = Yii::createObject($type);
    }
    
    /**
     * @param string|array|callable $type the object type.
     */
    public function setDbFixture($type)
    {
        $this->dbFixture = Yii::createObject($type);
    }
    
    /**
     * @param string|array|callable $type the object type.
     */
    public function setSharedData($type)
    {
        $this->sharedData = Yii::createObject($type);
    }
    
    /**
     * @return \pyd\testkit\FileSharedData
     */
    public function getSharedData()
    {
        return $this->sharedData;
    }
    
    /**
     * @todo store $type and create web driver manager instance on demand with
     * getWebDriverManager()?
     * 
     * @param string|array|callable $type the object type.
     */
    public function setWebDriverManager($type)
    {
        $this->webDriverManager = \Yii::createObject($type);
    }
    
    /**
     * @return \pyd\testkit\web\ObserverDriverManager
     */
    public function getWebDriverManager()
    {
        return $this->webDriverManager;
    }
    
    /**
     * Handle the 'setUpBeforeClass' event from the current test case.
     * 
     * @param string $testCaseClass currently processed test case class name
     */
    public function onSetUpBeforeClass($testCaseClass)
    {
        if (is_subclass_of($testCaseClass, '\pyd\testkit\web\TestCase')) {
            $this->getWebDriverManager()->activate($this->eventNotifier);
        }
        $this->eventNotifier->notify(TestCase::SETUP_BEFORE_CLASS, $testCaseClass);
    }
    
    /**
     * Handle the 'setUp' event from the current test case.
     * 
     * @param string $testCase currently executed test case instance
     */
    public function onSetUp(TestCase $testCase)
    {
        $testCase->dbFixture = $this->dbFixture;
        $testCase->yiiApp = $this->yiiApp;
        $this->eventNotifier->notify(TestCase::SETUP, $testCase);
    }
    
    /**
     * Handle the 'tearDown' event from the current test case.
     */
    public function onTearDown(TestCase $testCase )
    {
        $this->eventNotifier->notify(TestCase::TEAR_DOWN, $testCase);
    }
    
    /**
     * Handle and notify the 'tearDownAfterClass' event from current test case
     * class.
     * 
     * @param string $testCaseClass currently processed test case class name
     */
    public function onTearDownAfterClass($testCaseClass)
    {
        $this->eventNotifier->notify(TestCase::TEARDOWN_AFTER_CLASS, $testCaseClass);
    }
    
    /**
     * Register event observers.
     *
     * Order matters.
     */
    protected function registerObservers()
    {
        $notifier = $this->eventNotifier;
        $setUpBeforeClass = TestCase::SETUP_BEFORE_CLASS;
        $setUp = TestCase::SETUP;
        $tearDown = TestCase::TEAR_DOWN;
        $tearDownAfterClass = TestCase::TEARDOWN_AFTER_CLASS;
       
        $notifier->attachObserver($this->yiiApp->getConfigProvider(), $setUpBeforeClass);
        // db
        $notifier->attachObserver($this->yiiApp, $setUp);
        $notifier->attachObserver($this->yiiApp, $tearDown);
        $notifier->attachObserver($this->yiiApp, $tearDownAfterClass);

//        $this->events->registerObservers(
//            Events::SETUPBEFORECLASS,
//            [
//                $fixtureApp->getConfigProvider(),
//                $fixtureApp,
//                $fixtureDb,
//            ]);
//
//        $this->events->registerObservers(
//            Events::SETUP,
//            [
//                $fixtureDb,
//            ]);
//
//        $this->events->registerObservers(
//            Events::TEARDOWN,
//            [
//                $fixtureDb,
//                $fixtureApp,
//            ]);
//
//        $this->events->registerObservers(
//            Events::TEARDOWNAFTERCLASS,
//            [
//                $fixtureDb,
//                $fixtureApp,
//                $this,
//            ]);
    }
}