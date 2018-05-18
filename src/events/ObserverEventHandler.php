<?php
namespace pyd\testkit\events;

/**
 * Default implementation of the handleEvent() method required by the 'Observer'
 * interface.
 * 
 * @see \pyd\testkit\fixtures\yiiApp\Observer::handleEvent()
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
trait ObserverEventHandler
{
    /**
     * Handle an event.
     * 
     * @param \pyd\testkit\events\Event $event
     */
    public function handleEvent (Event $event)
    {
        $method = 'on' . ucfirst($event->name());
        $this->$method($event);
    }
}
