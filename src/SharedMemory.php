<?php
namespace pyd\testkit;

/**
 * This object properties store data to be sahred beetween PHP processes.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class SharedMemory extends \Fuz\Component\SharedMemory\SharedMemory
{
    /**
     * @var string the class name - with namespace - of the currently processed
     * test case
     */
    public $testCaseClassName;
    /**
     * @var integer the PHP's process ID used when the currently processed test
     * case started
     */
    public $testCaseStartPid;
}
