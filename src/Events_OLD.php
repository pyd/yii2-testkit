<?php
namespace pyd\testkit;

use yii\base\InvalidConfigException;

/**
 * Manage events and their observers.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Events extends \yii\base\Object
{
    const SETUPBEFORECLASS = 'setUpBeforeClass';
    const SETUP = 'setUp';
    const TEARDOWN = 'tearDown';
    const TEARDOWNAFTERCLASS = 'tearDownAfterClass';

    /**
     * @todo remove static
     * @var array list of valid event names
     */
    public static $validNames = ['setUpBeforeClass', 'setUp', 'tearDown', 'tearDownAfterClass'];

    /**
     * @var array each key is an event name and it's value an array of objects
     * registered as observers of this event
     */
    protected $observers = [];
    /**
     * @var \pyd\testkit\Manager
     */
    protected $testkit;

    public function init()
    {
        if (null === $this->testkit) {
            throw new InvalidConfigException("The " . get_class($this) . "::\$testkit property must be initialized.");
        }
    }

    /**
     * @return array
     */
    public function getObservers()
    {
        return $this->observers;
    }

    /**
     * Register observers of an event.
     *
     * @todo verify $observers: must contain instances, not null or other values
     * Maybe create an observer interface?
     *
     *
     * @param string $event event name
     * @param array $observers
     */
    public function registerObservers($event, array $observers)
    {
        $this->checkEventNameIsValid($event);

        if (empty($this->observers[$event])) {
            $this->observers[$event] = $observers;
        } else {
            $this->observers[$event] = array_merge ($this->observers[$event], $observers);
        }
    }

    /**
     * Unregister an observer.
     *
     * @param object $observer
     * @throws \yii\base\InvalidParamException*
     */
    public function unregisterObserver($observer)
    {
        foreach ($this->observers as $event => $observers) {
            $key = array_search($observer, $observers);
            if (false !== $key) {
                unset($this->observers[$event][$key]);
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
    public function trigger()
    {
        $args = func_get_args();
        $eventName = array_shift($args);
        $this->checkEventNameIsValid($eventName);
        array_push($args, $this->testkit);

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

    /**
     * Check if an event name is valid i.e. is listed in @see $validNames.
     *
     * @param string $name event name
     * @throws \yii\base\InvalidParamException invalid event name
     */
    protected function checkEventNameIsValid($name)
    {
        if (!in_array($name, self::$validNames)) {
            throw new \yii\base\InvalidParamException("Invalid event name '$name'.");
        }
    }

    /**
     * Setter for the @see $testkit property.
     *
     * @param \pyd\testkit\fixtures\Manager $testkit
     */
    protected function setTestkit(Manager $testkit)
    {
        $this->testkit = $testkit;
    }
}