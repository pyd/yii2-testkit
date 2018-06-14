<?php
namespace pyd\testkit\web\driver\selenium;

/**
 * Manage webdriver client - to send commands to selenium server.
 * 
 * Create and destroy webdriver client instance and its session.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Manager extends \yii\base\BaseObject implements \pyd\testkit\web\driver\Manager
{
    /**
     * @var \pyd\testkit\web\RemoteDriver 
     */
    protected $driver;
    
    public $url = 'http://localhost:4444/wd/hub';
    
    protected $desiredCapabilities;
    
    public $connectionTimeout;
    
    public $requestTimeout;
    
    public $driverClass = '\pyd\testkit\web\RemoteDriver';
    
    /**
     * @param \DesiredCapabilities $dc
     */
    public function setDesiredCapabilities(\DesiredCapabilities $dc)
    {
        $this->desiredCapabilities = $dc;
    }
    
    /**
     * Get the web driver instance.
     * 
     * @return \pyd\testkit\web\RemoteDriver 
     */
    public function getDriver()
    {
        return $this->driver;
    }
    
    /**
     * Driver instance exists and can send commands.
     * 
     * @warning if the {@see \RemoteWebDriver::close()} method is called in a
     * test, a browser with only one tab will close. Although this method will
     * still return true you commands will be sent, selenium will complain with
     * a \NoSuchWindow exception.
     * The {@see \RemoteWebDriver::quit()} method should be used to close
     * browser and session.
     *
     * @return boolean
     */
    public function driverIsReady()
    {
        // calling \RemoteWebDriver::quit() set command executor to null
        return null !== $this->driver && null !== $this->driver->getCommandExecutor();
    }
    
    /**
     * Create a {@see $driver} instance and start session with selenium webdriver.
     * 
     * @throws \yii\base\InvalidCallException
     */
    protected function createDriver()
    {
        try {
            $driverClass = $this->driverClass;
            $this->driver = $driverClass::create(
                    $this->url,
                    $this->desiredCapabilities,
                    $this->connectionTimeout,
                    $this->requestTimeout);
        } catch (\WebDriverCurlException $e) {
            throw new \yii\base\InvalidCallException("Cannot create web driver: " . $e->getMessage());
        }
    }
    
    /**
     * Close web driver session and destroy driver instance.
     */
    protected function destroyDriver()
    {
        $this->driver->quit();
        $this->driver = null;
    }
}
