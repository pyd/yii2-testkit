<?php
namespace pyd\testkit\fixtures\yiiApp;

use pyd\testkit\fixtures\yiiApp\Manager;
use pyd\testkit\events\Observer;

/**
 * Manage Yii application - as a fixture - by observing test case events.
 * 
 * # Goals:
 * - a Yii app instance can not be shared between test cases because the config
 * used to generate the app depends on the test case location in the tests tree;
 * - a Yii app instance must be available from the 'setUpbeforeClass' event to
 * the 'tearDownAfterClass' event in order to be available in test methods and
 * also for testkit components like 'fixtureDb';
 * 
 * Yii app lifecycle:
 * - creation when 'setUpBeforeClass' event occurs;
 * - renewall when 'tearDown' event occurs if app was destroyed by tester in test
 *   method or must not be shared {@see $shareyiiApp};
 * - destruction when 'endTestCase' event occurs;
 * 
 * @see reset() to reset $_SERVER variables and Yii app instance in a test method
 * 
 * The Yii app is not shared between php processes. Each test method executed in
 * isolation will have its own Yii app instance.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ManagerEventObserver extends Manager implements Observer
{
    use \pyd\testkit\events\ObserverEventHandler;
    
    /**
     * If set to false, the Yii app instance is renewed between each test
     * method execution. If set to true, the yii app instance is renewed only
     * when it has been destroyed by tester.
     * 
     * @see onTearDown()
     * @var boolean 
     */
    protected $shareYiiApp = false;
    
    /**
     * Handle the 'setUpBeforeClass' event.
     * 
     * Set the {@see ConfigProviderByDirectory::$testDirectory} based on the
     * location of the currently executed test case.
     * Store the value of {@see \pyd\testkit\TestCase::$shareYiiApp}.
     * Create Yii app instance.
     * 
     * @param \pyd\testkit\events\SetUpBeforeClass $event
     */
    protected function onSetUpBeforeClass(\pyd\testkit\events\SetUpBeforeClass $event)
    {
        $testCaseClass = $event->getTestCaseClass();
        $testCaseFile = (new \ReflectionClass($testCaseClass))->getFileName();
        $this->getConfigProvider()->setTestDirectory(dirname($testCaseFile));
        
        $this->shareYiiApp = $testCaseClass::$shareYiiApp;
        
        $this->initializeServerVars();
        $this->createYiiApp();
    }
    
    /**
     * Handle the 'tearDown' event from test case.
     * 
     * Yii app instance must be renewed if it was destroyed in the test method or
     * can not be be shared.
     * 
     * @param \pyd\testkit\events\TearDown $event
     */
    protected function onTearDown(\pyd\testkit\events\TearDown $event)
    {
        if (null === \Yii::$app || !$this->shareYiiApp) {
            $this->reset();
        }
    }
    
    /**
     * Handle the 'endTestCase' event.
     * 
     * Reset $_SERVER variables.
     * Destroy Yii app instance.
     * Reset {@see $shareYiiApp}.
     * 
     * @param \pyd\testkit\events\EndTestCase $event
     */
    protected function onEndTestCase(\pyd\testkit\events\EndTestCase $event)
    {
        $this->restoreServerVars();
        $this->destroyYiiApp();
        $this->shareYiiApp = null;
    }
}
