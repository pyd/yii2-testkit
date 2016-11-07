<?php
namespace pyd\testkit\web;

use yii\base\InvalidCallException;
use pyd\testkit\AssertionMessage;

/**
 * A page object.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Page extends \yii\base\Object
{
    use traits\ElementContainer;

    /**
     * @var string route part of the url
     */
    public $route;
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;
    /**
     * @var string|array|\WebDriverBy locator of the reference element used
     * to verify if the expected page is displayed
     * @see isDisplayed()
     */
    protected $refElementLocator;

    public function __construct(Driver $webDriver, array $config = [])
    {
        $this->webDriver = $webDriver;
        parent::__construct($config);
    }

    public function init()
    {
        $this->initLocators();
    }

    private $_request;

    /**
     * @return \pyd\testkit\web\Request
     */
    public function getRequest()
    {
        if (null === $this->_request) {
            $this->_request = new Request($this->webDriver, ['route' => $this->route]);
        }
        return $this->_request;
    }

    /**
     * @param string $command
     * @param array $params
     */
    protected function execute($command, array $params = [])
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
            throw new \Exception('Page ' . get_class($this) . ' is not properly displayed.');
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
        if (null === $this->refElementLocator) {
            throw new InvalidCallException('You must define ' . get_class($this) . '::$refElementLocator.' );
        }
        if ($this->hasElement($this->refElementLocator)) {
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
