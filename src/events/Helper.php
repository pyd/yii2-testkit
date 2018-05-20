<?php
namespace pyd\testkit\events;

/**
 * Tests helper.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Helper
{
    /**
     * Is a class name a test case class name.
     * 
     * Check if class exists and is a subclass of {@see \pyd\testkit\TestCase}.
     * 
     * @param string $className
     * @param boolean $exceptionOnError if set to false this method will return
     * a boolean. If set to true an exception is thrown instead of returning
     * false
     * @return boolean
     * @throws \Exception class does not exist or is not a subclass of
     * {@see \pyd\testkit\TestCase}
     */
    public static function isTestCaseClassName($className, $exceptionOnError = false)
    {
        if (class_exists($className) && is_subclass_of($className, '\pyd\testkit\TestCase')) {
            return true;
        } else if (!$exceptionOnError) {
            return false;
        } else {
            throw new \Exception("Class '$className' does not exist or is not a subclass of '\pyd\\testkit\TestCase'.");
        }
    }
}
