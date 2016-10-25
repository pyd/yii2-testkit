<?php
namespace pyd\testkit\web;

use pyd\testkit\Manager;
use pyd\testkit\Events;

/**
 * Manage web driver instance for web test case.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class DriverManager
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

    protected $driverConfig;

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
    public function onSetUp()
    {
        if (null === $this->driver) {
            try {
                $this->driver = Driver::create(
                        $this->driverConfig['url'],
                        $this->driverConfig['desiredCapabilities'],
                        $this->driverConfig['connectionTimeout'],
                        $this->driverConfig['requestTimeout']);
            } catch (\WebDriverCurlException $e) {
                throw new \yii\base\InvalidCallException("Cannot create web driver: " . $e->getMessage());
            }
        }
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
    }

    /**
     * Register this instance as an observer.
     *
     * @param \pyd\testkit\Events $events
     */
    public function registerAsObserver(Events $events)
    {
        $events->registerObservers(Events::SETUPBEFORECLASS, [$this]);
        $events->registerObservers(Events::SETUP, [$this]);
        $events->registerObservers(Events::TEARDOWN, [$this]);
        $events->registerObservers(Events::TEARDOWNAFTERCLASS, [$this]);
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
}
