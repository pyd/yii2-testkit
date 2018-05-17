<?php
namespace pyd\testkit\events;

/**
 * Event triggered by {@see pyd\testkit\TestCase::setUpBeforeClass()}.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class SetUpBeforeClass extends Event
{
    /**
     * @todo php7 set type 'string' for $testCaseClass params
     * 
     * @param string $testCaseClass class name of the test case for which this
     * event is triggered
     * @throws \yii\base\InvalidParamException if $testCaseClass is not an
     * existing class or is not a class that extends {@see pyd\testkit\TestCase}
     */
    public function __construct($testCaseClass)
    {
        if (!class_exists($testCaseClass) && !is_subclass_of('\pyd\testkit\TestCase', $testCaseClass)) {
            throw new \yii\base\InvalidParamException("'$testCaseClass' is not an existent class name or not a subclass of '\pyd\testkit\TestCase'");
        }
        parent::__construct($testCaseClass);
    }
    
    public static function name()
    {
        return 'setUpBeforeClass';
    }
    
    /**
     * @return string the class name of the test case that triggered this event
     */
    public function getTestCaseClass()
    {
        return $this->data;
    }
}
