<?php
namespace pyd\testkit\web\driver\selenium;

/**
 * Manage - create/destroy - the 'webdriver client' instance used to talk with
 * the selenium server according to the tests events.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ManagerEventObserver extends Manager implements \pyd\testkit\events\Observer
{
    use \pyd\testkit\events\ObserverEventHandler;

    /**
     * Use the same web driver session/browser VS renew session/browser for each
     * test in the current test case.
     * 
     * @var boolean 
     */
    protected $shareDriver;
    
    /**
     * Clear browser visible cookies between tests. This is relevant only if
     * {@see $shareDriver} is set to true.
     * 
     * @var boolean 
     */
    protected $clearCookies;

    /**
     * The current test case is a WEB test case - which requires a browser.
     * 
     * @var boolean 
     */
    protected $driverRequired;
    
    /**
     * Handle 'setUpBeforeClass' event.
     * 
     * @param \pyd\testkit\events\SetUpBeforeClass $event
     */
    public function onSetUpBeforeClass(\pyd\testkit\events\SetUpBeforeClass $event)
    {
        $testCaseClass = $event->getTestCaseClass();
        
        if (is_subclass_of($testCaseClass, '\pyd\testkit\web\TestCase')) {
            $this->driverRequired = true;
            $this->shareDriver = $testCaseClass::$shareBrowserSession;
            $this->clearCookies = !$testCaseClass::$shareCookies;
        }
    }
    
    /**
     * Handle 'setUp' event.
     * 
     * Make sure that a webdriver session is opened with selenium.
     * This must be a new session if webdriver is not meant to be shared.
     * If webdriver is shared, cookies might be deleted if needed. 
     * 
     * @param \pyd\testkit\events\SetUp $event
     */
    public function onSetUp(\pyd\testkit\events\SetUp $event)
    {
        if ($this->driverRequired) {
            
            if (!$this->driverIsReady()) {
                $this->createDriver();
            } else if (!$this->shareDriver) {
                $this->destroyDriver();
                $this->createDriver();
            } else if ($this->clearCookies) {
                $this->getDriver()->cookies()->deleteAll();
            }
            $event->getTestCase()->webDriver = $this->getDriver();
        }
    }
    
    /**
     * Handle 'testCaseEnd' event.
     * 
     * Destroy session and close browser.
     * 
     * @param \pyd\testkit\events\EndTestCase $event
     */
    public function onEndTestCase(\pyd\testkit\events\EndTestCase $event)
    {
        if ($this->driverRequired) {
            
            if ($this->driverIsReady()) {
                $this->destroyDriver();
            }
            
            $this->shareDriver = null;
            $this->clearCookies = null;
            $this->driverRequired = null;
        }
    }
}
