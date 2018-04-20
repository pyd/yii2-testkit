<?php
namespace pyd\testkit;

use pyd\testkit\Log;
use pyd\testkit\Testkit;

/**
 * Listen to test events and inform the test events dispatcher.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TestListener extends \PHPUnit_Framework_BaseTestListener
{
    const EVENT_TEST_SUITE_START = 'testSuiteStart';
    const EVENT_TEST_CASE_START = 'testCaseStart';
    const EVENT_TEST_CASE_END = 'testCaseEnd';
    const EVENT_TEST_SUITE_END = 'testSuiteEnd';
    const EVENT_TEST_METHOD_START = 'testMethodStart';
    const EVENT_TEST_METHOD_END = 'testMethodEnd';
    
    /**
     * @see startTestSuite
     * @see \PHPUnit_Framework_TestSuite::getName()
     * @var string test suite name i.e. a test case class name or a path/to/dir
     * depending on the target passed to the phpunit command
     */
    protected $testSuiteName;
    
    /**
     * Listen to the {@see EVENT_TEST_SUITE_START} and {@see EVENT_TEST_CASE_START}
     * events and inform the test events dispatcher with the
     * {@see \PHPUnit_Framework_TestSuite} instance as data.
     * 
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        
        if (null === $this->testSuiteName) {
            $this->informTestEventMediator(self::EVENT_TEST_SUITE_START, $suite);
            $this->testSuiteName = $suiteName;
        }
        if (class_exists($suiteName)) {
            $this->informTestEventMediator(self::EVENT_TEST_CASE_START, $suite);
        }
    }
    
    /**
     * Listen to the {@see EVENT_TEST_METHOD_START} event and inform the test
     * events dispatcher with the {@see \pyd\testkit\TestCase} instance as data.
     * 
     * @param \PHPUnit_Framework_Test $test
     */
    public function startTest(\PHPUnit_Framework_Test $test)
    {
        $this->informTestEventMediator(self::EVENT_TEST_METHOD_START, $test);
    }

    /**
     * Listen to the {@see EVENT_TEST_METHOD_START} event and inform the test
     * events dispatcher with the {@see \pyd\testkit\TestCase} instance as data.
     * 
     * @param \PHPUnit_Framework_Test $test
     * @param float $time
     */
    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        $this->informTestEventMediator(self::EVENT_TEST_METHOD_END, $test);
    }

    /**
     * Listen to the {@see EVENT_TEST_SUITE_END} and {@see EVENT_TEST_CASE_END}
     * events and inform the test events dispatcher with the TestSuite instance
     * as data.
     * 
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        
        if (class_exists($suiteName)) {
            $this->informTestEventMediator(self::EVENT_TEST_CASE_END, $suite);
        }
        if ($suiteName = $this->testSuiteName) {
            $this->informTestEventMediator(self::EVENT_TEST_SUITE_END, $suite);
        }
    }
    
    /**
     * Inform the test event mediator of a test event.
     * 
     * @param string $eventName 
     * @param mixed $data
     */
    protected function informTestEventMediator($eventName, $data = null)
    {
        Testkit::$app->testEventMediator->trigger($eventName, $data);
    }
}
