<?php
namespace pyd\testkit;

/**
 * This object properties store data to be shared beetween PHP processes.
 *
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class SharedMemory extends \Fuz\Component\SharedMemory\SharedMemory
{
    const INITIAL_PID = 'initialPID';
}
