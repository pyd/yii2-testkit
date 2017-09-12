<?php
namespace pyd\testkit\fixtures;

use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

/**
 * Manage Yii app instance creation and destruction at the test case level i.e.
 * an instance won't be shared between test cases.
 *
 * As a fixtures base element - it's db component is used by the db fixture
 * manager - it must be available from the begining 'setUpBeforeClass' to the end
 * 'tearDownAfterClass' of a test case.
 *
 * By default, each test method will use it's own Yii app instance. This is because
 * the @see pyd\testkit\base\TestCase::$shareYiiApp is set to false, by default.
 *
 * If you set the @see pyd\testkit\base\TestCase::$shareYiiApp to true in a test
 * case, the same Yii app instance will be shared between all test methods of
 * this test case, unless it is deleted by the tester - usually at the
 * end of a test method. In that case a new instance will be created.
 *
 * If a test method is executed in isolation, regardless the
 * @see pyd\testkit\base\TestCase::$shareYiiApp value, it will use a fresh Yii
 * app instance.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class App extends base\App
{
    /**
     * @var boolean the Yii app instance can be shared between test methods of
     * the currently processed test case.
     * @see \pyd\testkit\base\TestCase::$shareYiiApp
     */
    protected $testCaseShareYiiApp;

    /**
     * Handle the 'setUpBeforeClass' event (when a test case starts and before
     * a test method is executed in isolation).
     *
     * Create a Yii app instance.
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
     * Handle the 'tearDown' event (after each test method execution).
     *
     * If the test method was executed in isolation, a Yii app instance was
     * created in a separate php process by the @see onSetUpBeforeClass() and
     * will be destroyed by the @see onTearDownAfterClass().
     *
     * If the test method was not executed in isolation:
     * - the Yii app instance is destroyed if it is not 'shared';
     * - a Yii app instance is created if it does not already exist;
     *
     * Create a yii app instance after a test method execution, even if it's the
     * last of a test case, ensure that an instance is available till the
     * tearDownAfterClass 'event'.
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
