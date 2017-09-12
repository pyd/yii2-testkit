<?php
namespace pyd\testkit\fixtures\yiiApp;

use Yii;
use pyd\testkit\TestCase;

/**
 * Manage Yii app instance creation and destruction at the test case level i.e.
 * an instance won't be shared between different test cases.
 *
 * The rule is, a Yii app instance must be available in each test method.
 * 
 * If the @see pyd\testkit\TestCase::$shareYiiApp property is set to false, which
 * is the default value, each test method will use a different Yii app instance.
 * 
 * If it set to true, a Yii app instance is created at the begining of the test
 * and is shared between test methods unless the instance is destroyed in a test.
 * In this case, a new Yii app instance will be created for the next test method
 * and shared.
 * 
 * If a test method is executed in isolation a new yii app instance is created
 * whatever the value of the @see pyd\testkit\TestCase::$shareYiiApp property.
 * 
 * Note that a Yii app instance is available from the 'setUpbeforeClass' to the
 * 'tearDownAfterClass' events so it can be used by other fixtures e.g the db
 * fixture manager needs its 'db' component...
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ObserverAppManager extends AppManager
{
    /**
     * Handle the 'setUp' event.
     *
     * A Yii app instance must be available.
     *
     * @param string $testCase the currently executed test case
     */
    public function onSetUp(TestCase $testCase)
    {
        if (null === Yii::$app) {
            $this->create();
        }
    }

    /**
     * Handle the 'tearDown' event.
     */
    public function onTearDown(TestCase $testCase)
    {
        if (!$testCase->shareYiiApp) {
            $this->destroy();
        }
        if (null === Yii::$app) {
            $this->create();
        }
    }

    /**
     * Handle the 'tearDownAfterClass' event.
     */
    public function onTearDownAfterClass()
    {
        $this->destroy();
    }
}
