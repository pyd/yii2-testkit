<?php
namespace pyd\testkit\fixtures;

use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

/**
 * Make Yii app instance available in each test case.
 *
 * A Yii app instance is created by the @see onSetUpBeforeClass() handler i.e.
 * when a test case starts or before a test method is executed in isolation.
 * It is destroyed by the @see onTearDownAfterClass() handler i.e. when a test
 * case ends or after a test method executed in isolation.
 *
 * At least one Yii app instance is created for each test case i.e. it cannot be
 * shared between different test cases.
 *
 * When a test method is executed in isolation, it will use it's own Yii app
 * instance - created by the @see onSetUpBeforeClass() handler.
 *
 * When the @see pyd\testkit\base\TestCase::$shareYiiApp is set to false, each
 * test method - not executed in isolation - will use it's own Yii app instance.
 * When the @see pyd\testkit\base\TestCase::$shareYiiApp is set to true, each
 * test method - not executed in isolation - will use the same Yii app instance
 * unless this instance is deleted by the tester.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class App extends base\App
{
    /**
     * @var boolean Yii app instance does not have to be renewed for each test
     * method in the currently processed test case.
     * @see \pyd\testkit\base\TestCase::$shareYiiApp
     */
    protected $testCaseShareYiiApp;

    /**
     * Handle the 'setUpBeforeClass' event.
     *
     * A Yii app instance is created when a test case starts or before a test
     * method executed in isolation.
     *
     * @param string $testCaseClassName class name of the currently executed
     * test case
     */
    public function onSetUpBeforeClass($testCaseClassName)
    {
        $this->testCaseShareYiiApp = $testCaseClassName::$shareYiiApp;
        $this->create();
    }

    /**
     * Handler the 'tearDown' event.
     *
     * If the test method was executed in isolation, the Yii app will be
     * destroyed by the @see onTearDownAfterClass() handler.
     *
     * If the test method was not executed in isolation:
     * - the Yii app instance is destroyed if it is not shared;
     * - a Yii app instance is created if it does not already exist;
     */
    public function onTearDown(\pyd\testkit\base\TestCase $testCase)
    {
        if (!$testCase->isInIsolation()) {
            if (null !== \Yii::$app && !$this->testCaseShareYiiApp) {
                $this->destroy();
            }
            if (null === \Yii::$app) {
                $this->create();
            }
        }
    }

    /**
     * Handle the 'tearDownAfterClass' event.
     *
     * The Yii app instance is destroyed at the end of a test case or after
     * a test method executed in isolation.
     */
    public function onTearDownAfterClass($testCaseClassName, $testCaseEnd)
    {
        $this->destroy();
    }
}
