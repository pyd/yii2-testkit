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
 * # Observer lifecycle.
 * Event 'setUpBeforeClass':
 *  - load bootstrap files;
 *  - set $_SERVER variables;
 *  - create Yii app;
 * 
 * Event 'tearDown' (if yii app is null OR must not be shared {@see $shareYiiApp}):
 *  - reset $_SERVER;
 *  - renew Yii app;
 * 
 * Event 'endTestCase':
 *  - reset $_SERVER;
 *  - destroy Yii app;
 * 
 * Note: if the {@see \pyd\testkit\TestCase::$shareYiiApp} property is set to
 * false, the tester can use {@see reset()} to force the Yii app instance and
 * $_SERVER renewall.
 * 
 * If a test method is executed in isolation, a new yii app instance is created
 * whatever the value of the {@see \pyd\testkit\TestCase::$shareYiiApp} property.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ManagerEventObserver extends Manager implements Observer
{
    use \pyd\testkit\events\ObserverEventHandler;
    
    /**
     * If set to false, the Yii app instance is renewed between each test
     * method execution.
     * 
     * @var boolean 
     */
    protected $shareYiiApp = false;
    
    /**
     * Handle the 'setUpBeforeClass' event.
     * 
     * Inform app config provider of the directory of the current test case so
     * it can generate config.
     * Save value of the {@see \pyd\testkit\TestCase::$shareYiiApp} property.
     * Load bootstrap files.
     * Set $_SERVER variables.
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
        
        $this->loadBootstrapFiles();
        $this->setServerVars();
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
     * Destroy app instance.
     * Reset {@see $shareYiiApp}.
     * 
     * @param \pyd\testkit\events\EndTestCase $event
     */
    protected function onEndTestCase(\pyd\testkit\events\EndTestCase $event)
    {
        $this->resetServerVars();
        $this->destroyYiiApp();
        $this->shareYiiApp = null;
    }
}
