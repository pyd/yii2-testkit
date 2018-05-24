<?php
namespace pyd\testkit\events;

use pyd\testkit\TestCase;

/**
 * SetUp event triggered by {@see pyd\testkit\TestCase::setUp()}.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class SetUp extends Event
{
    /**
     * Instance of the test case that did trigger this event.
     * 
     * @var \pyd\testkit\TestCase
     */
    protected $testCase;
    /**
     * @var boolean test method will be executed in a separate process 
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
     * Get name of this event.
     * 
     * @return string
     */
    public static function name()
    {
        return 'setUp';
    }
    
    /**
     * Get instance of the currently executed test case.
     * 
     * @return \pyd\testkit\TestCase
     */
    public function getTestCase()
    {
        return $this->testCase;
    }
    
    /**
     * @return boolean test method will be executed in a separate process
     */
    public function getTestIsInIsolation()
    {
        return $this->testIsInIsolation;
    }
    
}
