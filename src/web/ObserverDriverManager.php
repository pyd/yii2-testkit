<?php
namespace pyd\testkit\web;

use pyd\testkit\Manager;
use pyd\testkit\EventNotifier;
use pyd\testkit\web\TestCase;

/**
 * Manage web driver instance for web test case.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ObserverDriverManager extends \yii\base\Object
{
    /**
     * @var string Selenium server listening url
     */
    public $seleniumUrl = 'http://localhost:4444/wd/hub';
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $driver;
    /**
     * @var string $driver class name 
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
        $class = $testCaseClassName;
        $this->shareDriver = $class::$shareWebDriver;
        $this->clearCookies = $class::$clearCookies;
        $this->driverConfig = $class::webDriverConfig();
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
     */
    public function onTearDownAfterClass()
    {
        $this->destroyDriver(false);
        $this->eventNotifier->detachObserver($this);
    }

    /**
     * Close all browser windows and ends selenium session.
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
            throw new \yii\base\InvalidCallException("Cannot destroy web driver. Instance is null.");
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
