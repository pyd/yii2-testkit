<?php
namespace pyd\testkit\events;

use pyd\testkit\events\Event;
use pyd\testkit\events\Observer;
use yii\base\InvalidParamException;

/**
 * Receive event notification and inform observers.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Mediator extends \yii\base\BaseObject
{
    protected $observers = [];
    
    /**
     * Register an observer that wants to be informed of an event.
     * 
     * @todo php7 set type 'string' for $eventName param
     * 
     * @param string $eventName
     * @param pyd\testkit\events\Observer $observer
     * @throws \LogicException the observer is already registered for this event
     */
    public function registerObserver($eventName, Observer $observer)
    {
        if (!$this->eventHasObserver($eventName, $observer)) {
            $this->observers[$eventName][spl_object_hash($observer)] = $observer;
        } else {
            throw new \LogicException ("Observer of class " .
                get_class($observer) . " already observes '$eventName' event.");
        }
    }
    
    /**
     * Unregister an observer that don't want to be informed of an event anymore.
     * 
     * @todo php7 set type 'string' for $eventName param
     * 
     * @param string $eventName
     * @param pyd\testkit\events\Observer $observer
     * @throws \LogicException the observer is not registered for this event
     */
    public function unregisterObserver($eventName, Observer $observer)
    {
        if ($this->eventHasObserver($eventName, $observer)) {
            unset($this->observers[$eventName][spl_object_hash($observer)]);
        } else {
            throw new \LogicException("Observer of class " .
                get_class($observer) . " does not observes '$eventName' event.");
        }
    }
    
    /**
     * Inform observers of an event that it occurs.
     * 
     * Note: nothing will happen if there's no observers for this event.
     * 
     * @todo php7 set type 'string' for $eventName param
     * 
     * @param pyd\testkit\events\Event $event
     */
    public function informObservers(Event $event)
    {
        $eventName = $event::name();
        if (isset($this->observers[$eventName])) {
            foreach ($this->observers[$eventName] as $observer) {
                $observer->handleEvent($event);
            }
        }
    }
    
    /**
     * Check if an observer is registered to be informed of an event.
     * 
     * @todo php7 set type 'string' for $eventName param
     * 
     * @param string $eventName
     * @param pyd\testkit\events\Observer $observer
     * @return boolean
     */
    public function eventHasObserver($eventName, Observer $observer)
    {
        // there may not be a key named $eventName
        return array_key_exists($eventName, $this->observers) &&
                array_key_exists(spl_object_hash($observer), $this->observers);
    }
    
    /**
     * Set observers for several events in one time.
     * 
     * ```php
     * Yii::createObject([
     *      'class' => '\pyd\testkit\events\Mediator,
     *      'observers' => [
     *          'eventName' => ['componentId', $observerInstance,...],
     *          'anotherEventName' => [...],
     *          ...
     *      ]
     * ]);
     * ```
     * An observer can be an instance of {@see \pyd\testkit\events\Observer} or
     * the ID of a component of this class.
     * Observer are registered for an event in the same order.
     * 
     * @param array $observers each item must be an array of observers - instance
     * od component ID - indexed by an event name
     * @throws InvalidParamException an observer is neither an instance of Observer
     * nor the ID of such a component
     */
    public function setObservers(array $observers)
    {
        $app = Testkit::$app;
        
        foreach ($observers as $eventName => $observers) {
            
            foreach ($observers as $observer) {
                
                if (is_string($observer)) {
                    if ($app->has($observer)) {
                        $observer = $testkitApp->get($observer);
                    } else {
                        throw new InvalidParamException("Unknown component ID "
                                . "'$observer'.");
                    }
                } else if (!$observer instanceof Observer) {
                    throw new InvalidParamException("An observer must be an "
                            . "instance or a component ID");
                }
                $this->registerObserver($eventName, $observer);
            }
        }
    }
            
            
}
