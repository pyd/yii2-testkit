<?php
namespace pyd\testkit\web;

use yii\base\InvalidCallException;
use pyd\testkit\AssertionMessage;

/**
 * @brief ...
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Page extends \yii\base\Object
{
    use traits\ElementContainer;

    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;

    public $route;

    public $refElementLocator;

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
            $this->_request = new Request($this->webDriver);
        }
        return $this->_request;
    }

    /**
     * @todo delete this is for building only
     * @param type $command
     * @param array $params
     */
    protected function execute($command, array $params = [])
    {

    }

    public function load(array $urlParams = [], $verifyDisplay = true)
    {
        if (null === $this->route) {
            throw new InvalidCallException("Property " . get_class($this) . "::\$route must be initialized to load the page.");
        }

        $this->getRequest()->sendViaGet($this->route, $urlParams);

        if ($verifyDisplay && !$this->isDisplayed()) {
            throw new \Exception('Page ' . get_class($this) . ' is not properly displayed.');
        }
    }

    /**
     * Verify that this page is being displayed in the browser.
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
}
