<?php
namespace pyd\testkit\web;

use yii\base\InvalidCallException;
use pyd\testkit\AssertionMessage;

/**
 * A page object.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Page extends base\ElementContainer
{
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
     * Send a command to selenium.
     * @param string $command
     * @param array $params
     */
    public function execute($command, array $params = [])
    {
        return $this->webDriver->execute($command, $params);
    }

    public function findId($location)
    {
        $by = $this->locationToWebDriverBy($location);
        $response = $this->execute(\DriverCommand::FIND_ELEMENT, ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        return $response['ELEMENT'];
    }

    public function findIds($location)
    {
        $ids = [];
        try {
            $response = $this->execute(\DriverCommand::FIND_ELEMENTS, ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        } catch (\Exception $e) {
            throw $e;
        }
        foreach ($response as $item) $ids[] = $item['ELEMENT'];
        return $ids;
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
        if (null !== $this->refElementLocator) {
            list($method, $value) = $this->refElementLocator;
            if ($this->hasElement(\WebDriverBy::$method($value))) {
                AssertionMessage::set('Page ' . get_class($this) . ' is displayed.');
                return true;
            } else {
                AssertionMessage::set('Page '  . get_class($this) . ' is not displayed.');
                return false;
            }
        } else {
            throw new InvalidCallException('You must define ' . get_class($this) . '::$refElementLocator.' );
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
