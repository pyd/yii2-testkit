<?php
namespace pyd\testkit\web;

use yii\base\InvalidCallException;
use pyd\testkit\AssertionMessage;
use pyd\testkit\web\PageIsNotDisplayedException;

/**
 * A page object.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Page extends \yii\base\Object
{
    use base\ElementContainerTrait;

    /**
     * @var string route part of the url
     */
    public $route;
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;
    /**
     * @var string|array|\WebDriverBy location of the reference element used
     * to verify if the expected page is displayed
     * @see isDisplayed()
     */
    protected $referenceLocation;

    private $_request;

    public function __construct(\RemoteWebDriver $webDriver, $config = array())
    {
        $this->webDriver = $webDriver;
        parent::__construct($config);
    }

    public function init()
    {
        $this->initElementContainerTrait();
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->locators)) {
            return $this->findElement($this->locators[$name]);
        }
        throw new \yii\base\UnknownPropertyException("Getting unknown property " .get_class(). " '$name'.");
    }

    /**
     * @return \pyd\testkit\web\Request
     */
    public function getRequest()
    {
        if (null === $this->_request) {
            $this->_request = new \pyd\testkit\web\Request($this->webDriver, ['route' => $this->route]);
        }
        return $this->_request;
    }

    /**
     * Send a command to selenium.
     * @param string $command
     * @param array $params
     */
    public function execute($command, array $params = [])
    {
        return $this->webDriver->execute($command, $params);
    }

    /**
     * Load the page.
     *
     * @param array $urlParams
     * @param boolean $verifyDisplay
     * @throws InvalidCallException
     * @throws \Exception
     */
    public function load(array $urlParams = [], $verifyDisplay = true)
    {
        if (null === $this->route) {
            throw new InvalidCallException("Property " . get_class($this) . "::\$route must be initialized to load the page.");
        }

        $this->getRequest()->send($urlParams);

        $this->waitReadyStateComplete();

        if ($verifyDisplay && !$this->isDisplayed()) {
            throw new PageIsNotDisplayedException('Page ' . get_class($this) . ' is not properly displayed.');
        }
    }

    /**
     * Verify that this page is displayed in the browser window.
     *
     * @return boolean
     * @throws InvalidCallException
     */
    public function isDisplayed()
    {
        if (null === $this->referenceLocation) {
            throw new InvalidCallException('You must define ' . get_class($this) . '::$referenceLocation.' );
        }

        if ($this->hasElement($this->locationToWebDriverBy($this->referenceLocation))) {
            AssertionMessage::set('Page ' . get_class($this) . ' is displayed.');
            return true;
        } else {
            AssertionMessage::set('Page '  . get_class($this) . ' is not displayed.');
            return false;
        }
    }

    /**
     * Wait until the document.readyState returns 'complete'.
     *
     * @param int $timeout in seconds
     * @param int $interval in milliseconds
     */
    public function waitReadyStateComplete($timeout = 5, $interval = 500)
    {
        $this->webDriver->wait($timeout, $interval)->until(
            function(){
                return 'complete' === func_get_arg(0)->executeScript("return document.readyState;");
            },
            "After $timeout seconds waiting, document.readyState still not 'complete'."
        );
    }

    /**
     * Get page source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->webDriver->getPageSource();
    }

    /**
     * Get page title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->webDriver->getTitle();
    }
}
