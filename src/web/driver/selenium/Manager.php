<?php
namespace pyd\testkit\web\driver\selenium;

use pyd\testkit\web\Driver;
use pyd\testkit\web\RemoteDriver;


/**
 * Manage - create/destroy - the 'webdriver client' instance used to talk with
 * the selenium server.
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
        if (!$this->driverIsReady()) {
            $this->createDriver();
        }
        return $this->driver;
    }
    
    /**
     * Driver instance exists and can send commands.
     * 
     * Note that this method does not check if the browser is opened. If it's
     * not, the command will be sent but selenium will complain with a
     * \NoSuchWindow exception. This happens when the {@see \RemoteWebDriver::close}
     * method is called on a browser with only one tab.
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
    private function createDriver()
    {
        if (null === $this->desiredCapabilities) {
            $this->desiredCapabilities = \DesiredCapabilities::firefox();
        }
        
        $this->driver = RemoteDriver::create($this->url, $this->desiredCapabilities, $this->connectionTimeout, $this->requestTimeout);
    }
    
    /**
     * Close web driver session and destroy driver instance.
     */
    protected function destroyDriver()
    {
        if ($this->driverIsReady()) {
            $this->driver->quit();
        }
        $this->driver = null;
    }
}
