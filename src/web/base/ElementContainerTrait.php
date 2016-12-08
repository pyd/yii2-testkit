<?php
namespace pyd\testkit\web\base;

use pyd\testkit\AssertionMessage;
use yii\base\InvalidCallException;

/**
 * For objects that contain web elements.
 *
 * Object that uses this trait must:
 * - have an accessible $webDriver property;
 * - implement an execute($command, array $params = []) method;
 * - call the initiLocators() method during initialization;
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
trait ElementContainerTrait
{
    /**
     * List of @see \WebDriverBy instances indexed by aliases.
     *
     * @var array ['elementAlias' => $webDriverByInstance, ...]
     */
    private $locators = [];
    /**
     * @var string selenium command
     * @see findId
     */
    protected $cmdFindFirst;
    /**
     * @var string selenium command
     * @see findIds
     */
    protected $cmdFindAll;

    /**
     * @param string|\WebDriverBy $location locator alias - @see $locators - or \WebDriverBy instance
     * @return string ID of the first matching element
     */
    public function findId($location)
    {
        $by = $this->locationToWebDriverBy($location);
        $response = $this->execute($this->cmdFindFirst, ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
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
     * @param string $name locator alias
     * @return boolean
     */
    public function hasLocator($name)
    {
        return isset($this->locators[$name]);
    }

    /**
     * @param string|\WebDriverBy $location locator alias - @see $locators - or \WebDriverBy instance
     * @return pyd\testkit\base\Element
     */
    public function findElement($location)
    {
        return $this->createElement($this->findId($location));
    }

    /**
     * @param string|\WebDriverBy $location locator alias - @see $locators - or \WebDriverBy instance
     * @return array pyd\testkit\base\Element
     */
    public function findElements($location)
    {
        $elements = [];
        foreach ($this->findIds($location) as $id) {
            $elements[] = $this->createElement($id);
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
        $toString = is_string($location) ? $location : \pyd\testkit\web\elements\Helper::byToString($location);
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
     * @return \pyd\testkit\web\Element
     */
    protected function createElement($id)
    {
        return new Element($this->webDriver, $id);
    }

    protected function locationToWebDriverBy($location)
    {
        if (is_string($location)) {
            return $this->getLocator($location);
        } else if ($location instanceof \WebDriverBy) {
            return $location;
        } else if (is_array($location)) {
            list($method, $value) = $location;
            return \WebDriverBy::$method($value);
        }
        throw new \yii\base\InvalidParamException("Location param must be a string (locator alias) or an instance of \WebDriverBy." .
                gettype($location) . " given.");
    }

    /**
     * Initialize this trait.
     *
     * This method must be called by the object that uses this trait.
     */
    protected function initElementContainerTrait()
    {
        $this->initLocators();
        $this->initCommands();
    }

    /**
     * You can add locators here.
     */
    protected function initLocators() {}

    /**
     * Initialize @see $cmdFindFirst and @see $cmdFindAll properties.
     */
    protected function initCommands()
    {
        if ($this instanceof \pyd\testkit\web\element\Base) {
            $this->cmdFindFirst = \DriverCommand::FIND_CHILD_ELEMENT;
            $this->cmdFindAll = \DriverCommand::FIND_CHILD_ELEMENTS;
        } else {
            $this->cmdFindFirst = \DriverCommand::FIND_ELEMENT;
            $this->cmdFindAll = \DriverCommand::FIND_ELEMENTS;
        }
    }
}
