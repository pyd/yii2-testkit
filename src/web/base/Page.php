<?php
namespace pyd\testkit\web\base;

/**
 * Base class for page objects.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Page extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\web\Driver $driver
     */
    protected $driver;
    /**
     * @var \pyd\testkit\web\base\ElementLocator
     */
    protected $locator;

    /**
     * @param \pyd\testkit\web\Driver $webDriver
     * @param array $config
     */
    public function __construct(\pyd\testkit\web\Driver $webDriver, $config = array())
    {
        $this->driver = $webDriver;
        parent::__construct($config);
    }

    /**
     * Initialization.
     *
     * Create a @see $locator object if it does not exit yet.
     */
    public function init()
    {
        if (null === $this->locator) {
            $this->setLocator(new ElementLocator());
        }
    }

    /**
     * If $name is a locator alias, it will return this element.
     *
     * @param string $name
     * @return \pyd\testkit\web\base\Element
     */
    public function __get($name)
    {
        if ($this->locator->aliasExists($name)) {
            return $this->findElement($this->locator->get($name));
        }
        return parent::__get($name);
    }

    /**
     * This method set @see $locator and call @see initLocators().
     *
     * @param \pyd\testkit\web\base\ElementLocator $locator @see $locator
     */
    public function setLocator(\pyd\testkit\web\base\ElementLocator $locator)
    {
        $this->locator = $locator;
        $this->initLocators();
    }

    /**
     * @return \pyd\testkit\web\base\ElementLocator or subclass
     */
    public function getLocator()
    {
        return $this->locator;
    }

    /**
     * Return an object representing the first element in the DOM that matches
     * the $location.
     *
     * If there's no matching, a @see \NoSuchElementException is raised.
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
        return $this->driver->findElementAs($by, $type);
    }

    /**
     * Return an an array of object representing all elements in the DOM that
     * match the location.
     *
     * If there's no matching, an empty array is returned.
     *
     * @param \WebDriverBy|string|array $location target elements location
     * @see \pyd\testkit\web\base\ElementLocator::resolve()
     * @param string|array|callable $type a definition of the objects to be
     * created @see \Yii::createObject
     * @return \pyd\testkit\web\base\Element by default or a subclass depending
     * on the provided $type param.
     */
    public function findElements($location, $type = null)
    {
        $by = $this->locator->resolve($location);
        return $this->driver->findElementsAs($by, $type);
    }

    /**
     * Check if a web element is present in the DOM (visible or not).
     *
     * @param \WebDriverBy|string|array $location target element location
     * @see \pyd\testkit\web\base\ElementLocator::resolve()
     * @return boolean
     */
    public function hasElement($location)
    {
        $by = $this->locator->resolve($location);
        return $this->driver->hasElement($by);
    }

    /**
     * @return string the current page source
     */
    public function getSource()
    {
        return $this->driver->getPageSource();
    }

    /**
     * @return string the current page title
     */
    public function getTitle()
    {
        return $this->driver->getTitle();
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
