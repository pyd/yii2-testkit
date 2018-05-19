<?php
namespace pyd\testkit\events;

/**
 * Base class for events.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
abstract class Event
{
    /**
     * @return string name of this event
     */
    abstract public static function name();
}
