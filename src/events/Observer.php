<?php
namespace pyd\testkit\events;

use pyd\testkit\events\Event;

/**
 * Interface for classes that want to be informed when an event occurs.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
interface Observer
{
    public function handleEvent(Event $event);
}
