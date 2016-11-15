<?php
namespace pyd\testkit\web\base;

use pyd\testkit\AssertionMessage;

/**
 * Base class for objects that contain elements.
 *
 * @todo finalize comments...
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
abstract class ElementContainer
{
    /**
     * List of @see \WebDriverBy instances indexed by aliases.
     *
     * @var array ['elementAlias' => $webDriverByInstance, ...]
     */
    private $locators = [];
    /**
     * @var \RemoteWebDriver
     */
    protected $webDriver;
    /**
     * @var array default configuration used to create elements @see createElement
     */
    protected $elementDefaultConfig = ['class' => 'pyd\testkit\web\element\Base'];
    /**
     * @var string selenium command
     * @see findId
     */
    protected $cmdFindOne;
    /**
     * @var string selenium command
     * @see findIds
     */
    protected $cmdFindAll;

    public function __construct(\RemoteWebDriver $webDriver)
    {
        $this->webDriver = $webDriver;
        if ($this instanceof \pyd\testkit\web\element\Base) {
            $this->cmdFindOne = \DriverCommand::FIND_CHILD_ELEMENT;
            $this->cmdFindAll = \DriverCommand::FIND_CHILD_ELEMENTS;
        } else {
            $this->cmdFindOne = \DriverCommand::FIND_ELEMENT;
            $this->cmdFindAll = \DriverCommand::FIND_ELEMENTS;
        }
        $this->initLocators();
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->locators)) {
            return $this->findElement($name);
        }
        parent::__get($name);
    }

    /**
     * Send a command to the selenium server.
     *
     * @param string $command {@link \WebDriverCommand}
     * @param array $params command params
     * @return mixed
     */
    abstract public function execute($command, array $params = []);

    /**
     * @param string|\WebDriverBy $location locator alias - @see $locators - or \WebDriverBy instance
     * @return string ID of the first matching element
     */
    public function findId($location)
    {
        $by = $this->locationToWebDriverBy($location);
        $response = $this->execute($this->cmdFindOne, ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        return $response['ELEMENT'];
    }

    /**
     * @param string|\WebDriverBy $location locator alias - @see $locators - or \WebDriverBy instance
     * @return array IDs of matching elements
     */
    public function findIds($location)
    {
        $ids = [];
        $by = $this->locationToWebDriverBy($location);
        $response = $this->execute($this->cmdFindAll, ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        foreach ($response as $item) $ids[] = $item['ELEMENT'];
        return $ids;
    }

    /**
     * Add an element locator.
     *
     * @see $locators
     *
     * @param string $alias locator alias e.g 'loginForm'
     * @param \WebDriverBy $by e.g. \WebDriverBy::id('login-form');
     * @param boolean $overwrite if $alias already exists in @see $locators,
     * should the new \WebDriverby instance overwrite the existing one
     * @throws InvalidCallException $alias already exists in @see $locators
     * and $overwrite is set to false
     */
    public function addLocator($alias, \WebDriverBy $by, $overwrite = false)
    {
        if (!isset($this->locators[$alias]) || $overwrite) {
            $this->locators[$alias] = $by;
        } else {
            throw new InvalidCallException("Locator alias '$alias' already exists.");
        }
    }

    /**
     * Get a locator by its alias.
     *
     * @param string $alias locator alias e.g 'loginForm'
     * @return \WebDriverBy instance
     * @throws InvalidCallException alias $alias is not defined in @see $locators
     */
    public function getLocator($alias)
    {
        if (isset($this->locators[$alias])) return $this->locators[$alias];
        throw new InvalidCallException("Unknown locator alias '$alias'.");
    }

    /**
     * @return array @see $locators
     */
    public function getLocators()
    {
        return $this->locators;
    }

    /**
     * @param string|\WebDriverBy $location locator alias - @see $locators - or \WebDriverBy instance
     * @param string|array $config class name or config array used to create the element
     * @return pyd\testkit\base\Element
     */
    public function findElement($location, $config = null)
    {
        return $this->createElement($this->findId($location), $config);
    }

    /**
     * @param string|\WebDriverBy $location locator alias - @see $locators - or \WebDriverBy instance
     * @param string|array $config class name or config array used to create the elements
     * @return array pyd\testkit\base\Element
     */
    public function findElements($location, $config = null)
    {
        $elements = [];
        foreach ($this->findIds($location) as $id) {
            $elements[] = $this->createElement($id, $config);
        }
        return $elements;
    }

    /**
     * Varify that an element is present - visible or not - in this container.
     *
     * @param string|\WebDriverBy $location locator alias - @see $locators - or \WebDriverBy instance
     * @return boolean
     */
    public function hasElement($location)
    {
        $toString = is_string($location) ? $location : \pyd\testkit\web\element\Helper::byToString($location);
        try {
            $this->findId($location);
            AssertionMessage::set("Element $toString is present.");
            return true;
        } catch (\NoSuchElementException $e) {
            AssertionMessage::set("Element $toString is not present.");
            return false;
        }
    }

    /**
     * @param string $id selenium ID of the web element
     * @param null|string|array $config of the element to be created. If null,
     * the @see elementDefaultConfig will be used. If not null, it will merge
     * with the latter.
     * @return \pyd\testkit\web\Element
     */
    protected function createElement($id, $config = null)
    {
        if (null === $config) {
            $config = $this->elementDefaultConfig;
        } else {
            $defaultConfig = $this->elementDefaultConfig;
            if (is_string($defaultConfig)) $defaultConfig = ['class' => $defaultConfig];
            if (is_string($config)) $config = ['class' => $config];
            $config = \yii\helpers\ArrayHelper::merge($defaultConfig, $config);
        }
        return \Yii::createObject($config, [$this->webDriver, $id]);
    }

    protected function locationToWebDriverBy($location)
    {
        if (is_string($location)) {
            return $this->getLocator($location);
        } else if ($location instanceof \WebDriverBy) {
            return $location;
        }
        throw new \yii\base\InvalidParamException("Location param must be a string (locator alias) or an instance of \WebDriverBy." .
                gettype($location) . " given.");
    }

    /**
     * You can add locators here.
     */
    protected function initLocators() {}
}
