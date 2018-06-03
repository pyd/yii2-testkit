<?php
namespace pyd\testkit\fixtures\db;

/**
 * This exception is thrown when an infinite loop is detected when adding Table
 * instances to the collection.
 */
class CircularDependencyException extends \Exception{
    
    /**
     * @param array $dependencyStack list of Table class names involved in the
     * circular dependency
     */
    public function __construct(array $dependencyStack) {
        parent::__construct($this->formatMessage($dependencyStack));
    }

    /**
     * Format exception message.
     * 
     * @param array $dependencyStack class names involved in the circular
     * dependency
     * @return string
     */
    protected function formatMessage(array $dependencyStack) {
        $loopOrigin = array_shift($dependencyStack);
        $msg = "\nCircular dependency detected for " . $loopOrigin . "class:";
        foreach ($dependencyStack as $classname) {
            $msg .= "\n\twhich depends on $classname";
        }
        $msg .= "\n\twhich depends on $loopOrigin <- back to origin";
        return $msg;
    }
}
