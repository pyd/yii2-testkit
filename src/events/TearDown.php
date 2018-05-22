<?php
namespace pyd\testkit\events;

use pyd\testkit\TestCase;

/**
 * TearDown event triggered by {@see pyd\testkit\TestCase::tearDown()}.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TearDown extends Event
{
    /**
     * Instance of the test case that did trigger this event.
     * 
     * @var \pyd\testkit\TestCase
     */
    protected $testCase;
    /**
     * @var boolean test method was executed in a separate process 
     */
    protected $testIsInIsolation;
    
    /**
     * @param TestCase $testCase
     */
    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
        $this->init();
    }
    
    protected function init()
    {
        $this->testIsInIsolation = $this->testCase->isInIsolation();
    }
    
    /**
     * @see $testCase
     * @return \pyd\testkit\TestCase
     */
    public function getTestCase()
    {
        return $this->testCase;
    }
    
    /**
     * @return boolean test method was executed in a separate process
     */
    public function getTestIsInIsolation()
    {
        return $this->testIsInIsolation;
    }
    
    /**
     * @return string this event name
     */
    public static function name()
    {
        return 'tearDown';
    }
}
