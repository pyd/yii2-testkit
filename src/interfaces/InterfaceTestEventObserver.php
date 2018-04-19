<?php
namespace pyd\testkit\interfaces;

/**
 * Interface for class that observes the test events.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
interface InterfaceTestEventObserver
{
    public function handleEvent($eventName, $data);
}
