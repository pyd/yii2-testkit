<?php
namespace pyd\testkit\web;

use pyd\testkit\Manager;
use pyd\testkit\EventNotifier;
use pyd\testkit\web\TestCase;

/**
 * Manage web driver instance(s) at the test case level.
 * 
 * @todo add possibility to define a default config for the web driver instance
 * in the phpunit boostrap file. The TestCase::webDriverConfig() could be used
 * as default config if none is defined in the bootstrap file or be merged with
 * the latter to customize the web driver instance at the test case level
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ObserverDriverManager extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\web\Driver the web driver instance
     */
    protected $driver;
    /**
     * @var string $driver class name of the web driver instance to create
     */
    protected $driverClass;
    /**
     * @var boolean if set to true the browser will be launched once for all
     * tests in a test case. If set to false, the browser will be launched and
     * closed for each test. If the former, you can still @see destroy() the
     * web driver to force it's creation for the next test method.
     */
    protected $shareDriver;
    /**
     * @var boolean if set to true, browser cookies will be deleted after each
     * execution of a test method. This property is relevant only when the
     * @see $shareDriver property is set to true.
     */
    protected $clearCookies;
    /**
     * @var array config used to create the web driver instance(s)
     * initialized by @see onSetUpBeforeClass
     */
    protected $driverConfig;
    /**
     * @var \pyd\testkit\EventNotifier
     * initialized by @see registerAsObserver
     */
    protected $eventNotifier;

    /**
     * @param string $className web driver class name
     */ 
    public function setDriverClass($className)
    {
        $this->driverClass = $className;
    }
    
    /**
     * @return \pyd\testkit\web\Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Handle 'setUpBeforeClass' event.
     *
     * @param string $testCaseClassName
     */
    public function onSetUpBeforeClass($testCaseClassName)
    {
        $this->shareDriver = $testCaseClassName::$shareWebDriver;
        $this->clearCookies = $testCaseClassName::$clearCookies;
        $this->driverConfig = $testCaseClassName::webDriverConfig();
    }

    /**
     * Handle 'setup' event.
     *
     * @throws \yii\base\InvalidCallException
     */
    public function onSetUp(TestCase $testCase)
    {
        if (null === $this->driver) {
            try {
                $driverClass = $this->driverClass;
                $this->driver = $driverClass::create(
                        $this->driverConfig['url'],
                        $this->driverConfig['desiredCapabilities'],
                        $this->driverConfig['connectionTimeout'],
                        $this->driverConfig['requestTimeout']);
            } catch (\WebDriverCurlException $e) {
                throw new \yii\base\InvalidCallException("Cannot create web driver: " . $e->getMessage());
            }
        }
        $testCase->webDriver = $this->driver;
    }
    
    /**
     * Handle 'tearDown' event.
     */
    public function onTearDown()
    {
        if (!$this->shareDriver) {
            $this->destroyDriver(false);
        } else if (null !== $this->driver && $this->clearCookies) {
            $this->driver->cookies()->deleteAll();
        }
    }

    /**
     * Handle 'tearDownAfterClass' event.
     * 
     * The web driver instance, if it exists is destroyed.
     * This observer is detached from the event notifier. 
     */
    public function onTearDownAfterClass()
    {
        $this->destroyDriver(false);
        $this->eventNotifier->detachObserver($this);
    }

    /**
     * Close all browser windows and ends selenium session.
     * 
     * @note the {@see $driver} property will be set to null.
     *
     * @param boolean $exceptionIfInstanceIsNull throw an exception if the web
     * driver instance is already null and cannot be destroyed
     * @throws \yii\base\InvalidCallException
     */
    public function destroyDriver($exceptionIfInstanceIsNull = true)
    {
        if (null !== $this->driver) {
            $this->driver->quit();
            $this->driver = null;
        } else if ($exceptionIfInstanceIsNull) {
            throw new \yii\base\InvalidCallException("Cannot destroy web driver. Instance does not exist.");
        }
    }
    
    /**
     * Register this instance as an observer.
     */
    public function activate(EventNotifier $eventNotifier)
    {
        $this->eventNotifier = $eventNotifier;
        $eventNotifier->attachObserver($this, TestCase::SETUP_BEFORE_CLASS);
        $eventNotifier->attachObserver($this, TestCase::SETUP);
        $eventNotifier->attachObserver($this, TestCase::TEAR_DOWN);
        $eventNotifier->attachObserver($this, TestCase::TEARDOWN_AFTER_CLASS);
    }
}
