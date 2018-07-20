<?php
namespace pyd\testkit\web\base;

/**
 * Base class for page objects.
 * 
 * This class is meant to represent a page that is already loaded in the browser
 * like an exception page. There are no $route property or load() method.
 * 
 * @see \pyd\testkit\web\Page to create classes that represent specific pages.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Page extends \yii\base\BaseObject
{
    /**
     * @var \pyd\testkit\web\RemoteDriver web driver instance
     */
    protected $webDriver;
    
    /**
     * @var \pyd\testkit\web\base\ElementLocator
     */
    protected $locator;

    /**
     * @param \pyd\testkit\web\RemoteDriver $webDriver
     * @param array $config
     */
    public function __construct(\pyd\testkit\web\RemoteDriver $webDriver, $config = array())
    {
        $this->webDriver = $webDriver;
        parent::__construct($config);
    }

    /**
     * Initialize {@see $locator} if its not.
     */
    public function init()
    {
        if (null === $this->locator) {
            $this->setLocator(new ElementLocator());
        }
        $this->initLocators();
    }

    /**
     * If $name is a locator alias, an instance of the web element found with
     * this locator will be returned.
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
     * @param \pyd\testkit\web\base\ElementLocator $locator
     */
    public function setLocator(\pyd\testkit\web\base\ElementLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @return \pyd\testkit\web\base\ElementLocator
     */
    public function getLocator()
    {
        return $this->locator;
    }

    /**
     * Return the first element in the DOM that matches the $location.
     *
     * If there's no matching, a {@see \NoSuchElementException} is raised.
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
        return $this->webDriver->findElementAs($by, $type);
    }

    /**
     * Return all elements in the DOM that match the location.
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
        return $this->webDriver->findElementsAs($by, $type);
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
        return $this->webDriver->hasElement($by);
    }

    /**
     * @return string source of the current page
     */
    public function getSource()
    {
        return $this->webDriver->getPageSource();
    }

    /**
     * @return string title of the current page
     */
    public function getTitle()
    {
        return $this->webDriver->getTitle();
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
     * @see init where this method is called
     * @see \pyd\testkit\web\base\ElementLocator
     */
    protected function initLocators() {}
}
