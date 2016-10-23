<?php
namespace pyd\testkit;

use yii\base\InvalidConfigException;

/**
 * Receive tests events and inform registered observers.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 *
 * @todo clean event constants
 */
class Events extends \yii\base\Object
{
    const SETUPBEFORECLASS = 'setUpBeforeClass';
    const SETUP = 'setUp';
    const TEARDOWN = 'tearDown';
    const TEARDOWNAFTERCLASS = 'tearDownAfterClass';

    /**
     * @var array list of event names supported by this class
     */
    public static $supportedEventNames = [
        self::SETUPBEFORECLASS,
        self::SETUP,
        self::TEARDOWN,
        self::TEARDOWNAFTERCLASS,
    ];

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
        $this->exceptionOnUnsupportedEventName($event);

        if (empty($this->observers[$event])) {
            $this->observers[$event] = $observers;
        } else {
            \yii\helpers\ArrayHelper::merge($this->observers[$event], $observers);
        }
    }



    /**
     * Inform registered observers that an event occurs.
     *
     *  ```php
     * // This method can take a variable number of arguments.
     * $eventsDispatcher->trigger(EventsDispatcher::SETUP, $arg1, $arg2, $arg3, ...);
     * // The first one must be the name of the event to dispatch @see $supportedEventNames
     * // Others will be passed as argument(s) to the observer method
     * // $testkit->onSetUp($arg1, $arg2, $arg3, ..., $testkitInstance)
     * // Note that the instance of \pyd\testkit\fixtures\Manager is added as last argument.
     * // $appConfig->onSetUp($arg1, $arg2, $arg3, ..., $testkitInstance)
     * // $App->onSetUp($arg1, $arg2, $arg3, ..., $testkitInstance)
     * ```
     *
     * @todo remove code and update doc on $testkitInstance added as last argument
     * @todo maybe add a log when the event name has no observer registered
     *
     * @param string $event
     * @param mixed list of arguments to be passed to the observer method
     * @throws \yii\base\InvalidCallException
     */
    public function trigger()
    {
        $args = func_get_args();
        $eventName = array_shift($args);
        $this->exceptionOnUnsupportedEventName($eventName);
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
     * Throw an InvalidParamException if the value of the $eventName argument is
     * not a supported event name.
     *
     * @see $supportedEventNames
     * @param string $eventName name of the event to verify
     * @throws \yii\base\InvalidParamException unsupported event name
     */
    protected function exceptionOnUnsupportedEventName($eventName)
    {
        if (!in_array($eventName, self::$supportedEventNames)) {
            throw new \yii\base\InvalidParamException("Unsupported event name '$eventName'.");
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