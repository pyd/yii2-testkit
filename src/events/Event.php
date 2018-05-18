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
     * @var mixed data related to this event 
     */
    protected $data;
    
    /**
     * @param mixed $data {@see $data}
     */
    public function __construct($data = null)
    {
        $this->data = $data;
        $this->init();
    }
    
    protected function init() {}
    
    /**
     * @return string name of this event
     */
    abstract public static function name();
}
