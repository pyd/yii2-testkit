<?php
namespace pyd\testkit\events;

use pyd\testkit\events\Helper;

/**
 * Event triggered when the {@see pyd\testkit\TestCase::setUpBeforeClass()}
 * method is executed.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class SetUpBeforeClass extends Event
{
    /**
     * @var class name of the currently executed test case 
     */
    protected $testCaseClass;
    
    /**
     * @param string $testCaseClass class name of the test case for which this
     * event is triggered
     * @throws \yii\base\InvalidParamException if $testCaseClass is not an
     * existing class or is not a class that extends {@see pyd\testkit\TestCase}
     */
    public function __construct($testCaseClass)
    {
        // if false an exception will be thrown
        if (Helper::isTestCaseClassName($testCaseClass, true)) {
            $this->testCaseClass = $testCaseClass;
        }
    }
    
    public static function name()
    {
        return 'setUpBeforeClass';
    }
    
    /**
     * @return string class name of the currently executed test case 
     */
    public function getTestCaseClass()
    {
        return $this->testCaseClass;
    }
}
