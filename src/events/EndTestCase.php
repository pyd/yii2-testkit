<?php
namespace pyd\testkit\events;

/**
 * Event triggered when a test case ends by
 * {@see \pyd\testkit\Testlistener::endTestSuite()}.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class EndTestCase extends Event
{
    /**
     * @var \PHPUnit_Framework_TestSuite
     */
    protected $suite;
    
    public function __construct(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->suite = $suite;
    }
    
    public static function name()
    {
        return 'endTestCase';
    }
}
