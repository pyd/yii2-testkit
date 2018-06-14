<?php
namespace pyd\testkit\web\driver;

/**
 * Description of ChromeDriver
 * 
 * @warning temporary class to manager client for chromedriver
 * @todo remove if not used
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ChromeDriver extends RemoteDriver
{
    protected static $executable = '/usr/bin/chromedriver';
    
    protected static $port = 9515;
    
    public static function start(\DesiredCapabilities $desired_capabilities = null, \ChromeDriverService $service = null)
    {
        if ($desired_capabilities === null) {
          $desired_capabilities = \DesiredCapabilities::chrome();
        }
        
        if ($service === null) {
          $service = $this->getService();
        }
        
        $executor = new \DriverCommandExecutor($service);
        $driver = new static();
        $driver->setCommandExecutor($executor)
               ->startSession($desired_capabilities);
        return $driver;
    }

    public function startSession($desired_capabilities) {
        $command = new \WebDriverCommand(
          null,
          \DriverCommand::NEW_SESSION,
          array(
            'desiredCapabilities' => $desired_capabilities->toArray(),
          )
        );
        $response = $this->executor->execute($command);
        $this->setSessionID($response->getSessionID());
    }
    
    /**
     * @return \DriverService
     */
    protected function getService()
    {
        return new \DriverService($this->executable, $this->port, "--port=$this->port");
    }
}
