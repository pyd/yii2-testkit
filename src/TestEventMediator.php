<?php
namespace pyd\testkit;

use yii\base\InvalidParamException;
use pyd\testkit\Testkit;
use pyd\testkit\interfaces\InterfaceTestEventObserver;

/**
 * Listen to the test events and inform registered observers when they occur.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TestEventMediator extends \yii\base\Object
{
    /**
     * Test event observers.
     * @see attach()
     * @var array each key should be an event name and its value an array of
     * observers {@see \pyd\testkit\interfaces\InterfaceTestEventObserver}
     */
    protected $observers;

    /**
     * Attach an observer to a test event.
     * @param string $eventName
     * @param \pyd\testkit\interfaces\InterfaceTestEventObserver $observer
     */
    public function attach($eventName, InterfaceTestEventObserver $observer)
    {
        $this->observers[$eventName][spl_object_hash($observer)] = $observer;
    }
    
    /**
     * Detach an observer from a test event.
     * @param string $eventName
     * @param pyd\testkit\interfaces\InterfaceTestEventObserver $observer
     * @throws InvalidParamException observer is not attached to this test event
     */
    public function detach($eventName, InterfaceTestEventObserver $observer)
    {
        unset($this->observers[$eventName][spl_object_hash($observer)]);
    }
    
    /**
     * Inform observers attached to a test event.
     * @param string $eventName
     * @param mixed $data data to be passed to the event handler of the observer
     */
    public function trigger($eventName, $data = null)
    {
        if (isset($this->observers[$eventName])) {
            foreach ($this->observers[$eventName] as $observer) {
                $observer->handleEvent($eventName, $data);
            }
        }
    }
    
    /**
     * Set observers.
     * This methods is meant to initialize the {@see $observers} property at
     * configuration time without observer instances but with their component IDs.
     * @param array $observers each key should be an event name and its value an
     * array of observers i.e. an instance implementing
     * {@see \pyd\testkit\interfaces\InterfaceTestEventObserver} or its
     * component ID
     */
    public function setObservers(array $observers)
    {
        $testkitApp = Testkit::$app;
        
        foreach ($observers as $eventName => $observers) {
            
            foreach ($observers as $observer) {
                
                if (is_string($observer)) {
                    if ($testkitApp->has($observer)) {
                        $observer = $testkitApp->get($observer);
                    } else {
                        throw new InvalidParamException("Unknown component ID  '$observer'.");
                    }
                }
                $this->attach($eventName, $observer);
            }
        }
    }
}
