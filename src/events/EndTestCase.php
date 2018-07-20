<?php
namespace pyd\testkit\events;

/**
 * Event triggered when a test case ends. i.e. when all its tests have been
 * executed and after 'tearDownAfterClass' and 'annotationAfterClass' event.
 * 
 * @see \pyd\testkit\Testlistener::endTestSuite() which triggers this event
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class EndTestCase extends Event
{
    /**
     * @var \PHPUnit_Framework_TestSuite
     */
    protected $suite;
    
    /**
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function __construct(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->suite = $suite;
    }
    
    public static function name()
    {
        return 'endTestCase';
    }
}
