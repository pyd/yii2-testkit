<?php
namespace pyd\testkit;

/**
 * Notify test case events to registered observers.
 * 
 * @todo create an interface to type $observer parameter in attach & detach
 * methods?
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class EventNotifier extends \yii\base\Object
{
    /**
     * @var array each key is an event name and it's value an array of objects
     * registered as observers of this event
     */
    protected $observers = [];
    
    /**
     * Get all observers | observers by event.
     * 
     * @param string $eventName if null the @see $observers property is
     * returned. If not null, the $observers[$eventName] array is returned.
     * @return array
     */
    public function getObservers($eventName = null)
    {
        if (null === $eventName) {
            return $this->observers;
        } else {
            return $this->observers[$eventName];
        }
    }
    
    /**
     * Attach an observer to an event.
     * 
     * @todo what if an observer is already attached to an event? Check for
     * duplicates.
     *
     * @param string $eventName
     * @param object $observer
     */
    public function attachObserver($observer, $eventName)
    {
        if (array_key_exists($eventName, $this->observers)) {
            array_push($this->observers[$eventName], $observer);
        } else {
            $this->observers[$eventName] = [$observer];
        }
    }
    
    /**
     * Detach an observer from all events or from a specific event.
     * 
     * @param object $observer
     * @param string|null $eventName event name
     */
    public function detachObserver($observer, $eventName = null)
    {
        foreach ($this->observers as $event => $observers) {
            
            // no need to search for an observer attached to another event
            if (null !== $eventName && $eventName !== $event) {
                continue;
            }
            $key = array_search($observer, $observers);
            if (false !== $key) {
                unset($this->observers[$event][$key]);
            }
            // no need to keep searching if the target event has been processed
            if (null !== $eventName && $eventName === $event) {
                break;
            }
        }
    }
    
    /**
     * Inform registered observers that an event occurs.
     * 
     * This method can take a variable number of arguments.
     *  ```php
     * $testkit->getEvents()->trigger(Events::SETUP, $arg1, $arg2, $arg3, ...);
     * ```
     * The first argument must be the name of the event.
     * @see $validNames
     *
     * Others arguments will be passed to the observer method with an extra
     * \pyd\testkit\fixtures\Manager instance as last argument.
     *
     * @param string $event
     * @param mixed list of arguments to be passed to the observer method
     * @throws \yii\base\InvalidCallException
     */
    public function notify()
    {
        $args = func_get_args();
        $eventName = array_shift($args);

        if (!empty($this->observers[$eventName])) {

            $method = 'on' . ucfirst($eventName);

            foreach ($this->observers[$eventName] as $observer) {

                if (is_callable([$observer, $method])) {
                    call_user_func_array([$observer, $method], $args);
                } else {
                    throw new \yii\base\InvalidCallException("Method " . get_class($observer) . "::$method does not exist or is not callable.");
                }
            }
        }
        return $this;
    }
}
