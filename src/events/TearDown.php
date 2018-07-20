<?php
namespace pyd\testkit\events;

use pyd\testkit\TestCase;

/**
 * Event triggered when the {@see pyd\testkit\TestCase::tearDown()} method is
 * executed.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TearDown extends Event
{
    /**
     * Instance of the currently executed test case 
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
    
    /**
     * @see $testCase
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

    public static function name()
    {
        return 'tearDown';
    }
}
