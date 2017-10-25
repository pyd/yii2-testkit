<?php
namespace pyd\testkit\web;

/**
 * A web element object.
 *
 * This class provides advanced features, especially to find child elements.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Element extends base\Element
{
    /**
     * @var \pyd\testkit\web\base\ElementFinder
     */
    protected $finder;
    /**
     * @var \pyd\testkit\web\base\ElementLocator
     */
    protected $locator;

    /**
     * Initialization.
     *
     * Set @see $finder if it's not already done.
     */
    public function init()
    {
        if (null === $this->finder) {
           $this->setFinder($this->driver->getElementFinder());
        }
        if (null === $this->locator) {
            $this->setLocator(new base\ElementLocator());
        }
        $this->initLocators();
    }

    /**
     *
     * @param type $name
     * @return type
     */
    public function __get($name)
    {
        if ($this->locator->aliasExists($name)) {
            return $this->findElement($this->locator->get($name));
        }
        return parent::__get($name);
    }

    /**
     * @param \pyd\testkit\web\base\ElementFinder $elementFinder
     */
    public function setFinder(base\ElementFinder $elementFinder)
    {
        $this->finder = $elementFinder;
    }

    /**
     * @return \pyd\testkit\web\base\ElementLocator
     */
    public function getLocator()
    {
        return $this->locator;
    }

    /**
     * @param \pyd\testkit\web\base\ElementLocator $elementLocator
     */
    public function setLocator(base\ElementLocator $elementLocator)
    {
        $this->locator = $elementLocator;
    }

    /**
     * Return an object representing the first element within this element that
     * matches the provided location.
     *
     * A \NoSuchElementException is raised if there's no matching element.
     *
     * @param \WebDriverBy|string|array $location target element location
     * @see \pyd\testkit\web\base\ElementLocator::resolve()
     * @param string|array|callable $type a definition of the object to be
     * created @see \Yii::createObject
     * @return \pyd\testkit\web\base\Element by default or a subclass depending
     * on the provided $type param.
     */
    public function findElement($location, $type = null)
    {
        $by = $this->locator->resolve($location);
        return $this->finder->findChildElement($by, $this->getID(), $type);
    }

    /**
     * Return an array of objects representing all elements within a web element
     * that matches the provided location.
     *
     * @param \WebDriverBy|string|array $location target element location
     * @see \pyd\testkit\web\base\ElementLocator::resolve()
     * @param string|array|callable $type a definition of the objects to be
     * created @see \Yii::createObject
     * @return array of \pyd\testkit\web\base\Element or subclass. An empty
     * array if no match was found.
     */
    public function findElements($location, $type = null)
    {
        $by = $this->locator->resolve($location);
        return $this->finder->findChildElements($by, $this->getID(), $type);
    }

    /**
     * Check if a web element is present within this element (visible or not).
     *
     * @param \WebDriverBy|string|array $location target element location
     * @see \pyd\testkit\web\base\ElementLocator::resolve()
     * @return boolean
     */
    public function hasElement($location)
    {
        $by = $this->locator->resolve($location);
        return $this->finder->hasChildElement($by, $this->getID());
    }

    /**
     * This method is the dedicated place to add locations for web elements.
     *
     * ```php
     * parent::initLocators();
     * $this->locator->add('loginForm', \WebDriverBy::id('login-form'));
     * // or
     * $this->locator->add('loginForm', ['id', 'login-form']);
     * ```
     * @see \pyd\testkit\web\base\ElementLocator
     */
    protected function initLocators() {}
}
