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
    /**
     * @see startTestSuite
     * @see \PHPUnit_Framework_TestSuite::getName()
     * @var string test suite name i.e. a test case class name or a path/to/dir
     * depending on the target passed to the phpunit command
     */
    protected $suiteName;
    
    /**
     * Start of a test suite or of a test case.
     * 
     * @param \PHPUnit_Framework_TestSuite $suite
     * 
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        
        if (null === $this->suiteName) {
            $this->suiteName = $suiteName;
        }
        
        if (class_exists($suiteName)) {
            // inform observers of 'startTestCase' event
        }
    }

    /**
     * End of a test suite or of a test case.
     * 
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        
        if (class_exists($suiteName)) {
            $this->informObservers(new events\EndTestCase($suite));
        }
        
        if ($suiteName = $this->suiteName) {
            // inform observers of 'endTestSuite' event.
        }
    }
    
    /**
     * Inform observers of an event.
     * 
     * @param \pyd\testkit\events\Event $event 
     */
    protected function informObservers(events\Event $event)
    {
        Testkit::$app->eventMediator->informObservers($event);
    }
}
