<?php
namespace pyd\testkit\events;

/**
 * Event triggered by {@see pyd\testkit\TestCase::tearDownAfterClass()}.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TearDownAfterClass extends SetUpBeforeClass
{
    /**
     * @return string this event name
     */
    public static function name()
    {
        return 'tearDownAfterClass';
    }
}
