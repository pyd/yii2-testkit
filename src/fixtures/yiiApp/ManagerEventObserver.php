<?php
namespace pyd\testkit\fixtures\yiiApp;

use pyd\testkit\fixtures\yiiApp\Manager;
use pyd\testkit\events\Observer;
use pyd\testkit\events\SetUpBeforeClass;
use pyd\testkit\events\TearDownAfterClass;

/**
 * Manage Yii application - as a fixture - by observing test case events.
 * 
 * Goals:
 * - a Yii app instance can not be shared between test cases because the config
 * used to generate the app depends on the test case location in the tests tree;
 * - a Yii app instance must be available from the start of the test case till
 * its end so it can be used in test methods but also before and after them by
 * other testkit components like db fixture manager which requires its 'db'
 * component;
 * 
 * Yii app lifecycle:
 * - 'setUpBeforeClass' event: creation;
 * - 'setUp' event: creation if app does not exist i.e. was destroyed by tester;
 * - 'tearDown' event:
 *      - creation if app does not exist i.e. was destroyed by tester;
 *      - auto renewall if {@see \pyd\testkit\TestCase::$shareYiiApp} property
 *        is set to true;
 * - 'tearDownAfterClass' event: destruction;
 * 
 * If the {@see \pyd\testkit\TestCase::$shareYiiApp} property is set to
 * false, the tester can destroy the instance at the end of a test method to
 * force the creation of a new instance at 'tearDown' event.
 * 
 * The Yii app instance can be reseted by the tester in a test method with the
 * {@see \pyd\testkit\fixtures\yiiApp\Manager::reset()} method.
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
     * Share Yii app instance between test methods of a test case.
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
     * @param pyd\testkit\events\SetUpBeforeClass $event
     */
    protected function onSetUpBeforeClass(SetUpBeforeClass $event)
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
     * Handle the 'tearDownAfterClass' event.
     * 
     * Reset $_SERVER variables.
     * Destroy app instance.
     * 
     * @param pyd\testkit\events\TearDownAfterClass $event
     */
    protected function onTearDownAfterClass(TearDownAfterClass $event)
    {
        $this->resetServerVars();
        $this->destroyYiiApp();
    }
    
    /**
     * Handle the 'testCaseEnd' event.
     * 
     * Destroy the Yii app instance {@see destroy()}.
     * 
     * @param string $testCaseClass name of the test case that was executed
     */
//    protected function onEventTestCaseEnd($testCaseClass)
//    {
//        $this->resetServerVars();
//        $this->destroyYiiApp();
//        $this->shareYiiApp = null;
//    }
    
    /**
     * Handle the 'testMethodEnd' event.
     * 
     * @param \pyd\testkit\TestCase $testCase
     */
//    protected function onEventTestMethodEnd($testCase)
//    {
//        if (!$testCase->isInIsolation()) {
//            if (null === \Yii::$app || !$this->shareYiiApp) {
//                $this->reset();
//            }
//        }
//    }
}
