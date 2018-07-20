<?php
namespace pyd\testkit\events;

use pyd\testkit\TestCase;

/**
 * Event triggered when the {@see pyd\testkit\TestCase::setUp()} method is
 * executed.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class SetUp extends Event
{
    /**
     * Currently executed test case.
     * 
     * @var \pyd\testkit\TestCase
     */
    protected $testCase;
    
    /**
     * @var boolean current test is executed in isolation
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
    
    public static function name()
    {
        return 'setUp';
    }
    
    /**
     * Get the instance of the currently executed test case.
     * 
     * @return \pyd\testkit\TestCase
     */
    public function getTestCase()
    {
        return $this->testCase;
    }
    
    /**
     * @return boolean current test is executed in isolation
     */
    public function getTestIsInIsolation()
    {
        return $this->testIsInIsolation;
    }
    
}
