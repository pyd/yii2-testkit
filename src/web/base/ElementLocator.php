<?php
namespace pyd\testkit\web\base;

use yii\base\InvalidParamException;

/**
 * Manage aliases for web element location.
 *
 * <code>
 * // add a 'loginForm' alias with an array location
 * $page->getLocator()->add('loginForm', ['id', 'login-form']);
 * // or with a \WebDriverBy location
 * // $page->getLocator()->add('loginForm', \WebDriverBy::id('login-form'));
 * 
 * // use the alias to get the web element instance
 * $this->assertTrue($page->loginForm->isDisplayed());
 * </code>
 *
 * @see \WebDriverBy
 * 
 * @todo when locator is an array, its conversion to a \WebDriver object should
 * be handled by the ElementFinder class. This job should not be the responsability
 * of the ElementLocator. When declaring a locator property in a class as an array,
 * it is not possible to use $this->webDriver->findElement($this->locator)
 * because this method param must be a WebDriverBy instance.
 * We can modify the RemoteDriver::findElement - findElements, findElementAs ... -
 * methods to accept locator as an array.
 * We can also make the ElementLocator::fromArray method reachable from
 * anywhere - may be a static method - to convert array to WebDriverBy.
 * locator resolution here should only handle string locators - aliases. Array
 * to WebDriverBy resolution should be done elsewhere...
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ElementLocator
{
    /**
     * @var array of $alias => $webDriverByInstance pairs
     */
    private $map = [];

    /**
     * Add a \WebDriverby instance indexed by an alias to the @see $map property.
     *
     * @param string $alias alias of the location
     * @param \WebDriverBy|array $location if an array @see fromArray()
     * @param boolean $overwrite if set to true and $alias already exists, it's
     * location will be overwriten by the new one.
     * @throws InvalidParamException:
     * - $location param is not of the expected type;
     * - the alias param already exists and it's location cannot be overwriten
     * unless the $averwrite param is set to true, which is not the case by default
     */
    public function add($alias, $location, $overwrite = false)
    {
        if (!is_string($alias)) {
            throw new InvalidParamException("Param 'alias' must be a string. " . gettype($alias) . " provided.");
        }
        
        if (!isset($this->map[$alias]) || $overwrite) {

            if ($location instanceof \WebDriverBy) {
                $this->map[$alias] = $location;
            } else if (is_array($location)) {
                $this->map[$alias] = $this->fromArray($location);
            } else {
                throw new InvalidParamException("Location param must be an array or a
                    \\WebDriverBy instance. The param you provided is of type '" . gettype($location) . "'.");
            }

        } else {
            throw new InvalidParamException("Alias '$alias' already exists. You
                    can force overwrite using the eponym param.");
        }
    }

    /**
     * A location alias exists.
     *
     * @param string $alias
     * @return boolean
     */
    public function aliasExists($alias)
    {
        return isset($this->map[$alias]);
    }

    /**
     * Get a \WebDriverBy location instance from its alias.
     *
     * @param string $alias
     * @return \WebDriverBy
     * @throws InvalidParamException unknown alias
     */
    public function get($alias)
    {
        if (isset($this->map[$alias])) return $this->map[$alias];
        throw new \yii\base\InvalidParamException("No alias named '$alias' was found.");
    }

    /**
     * @return array all \WebDriver
     */
    public function getAll()
    {
        return $this->map;
    }

    /**
     * Clear all stored aliases and their @see \WebDriverBy instances.
     */
    public function clear()
    {
        $this->map = [];
    }

    /**
     * Create a \WebDriverBy instance from an array.
     *
     * This array must contain 2 items (NOT a key => value pair).
     *
     * The first must be a the name of a WebDriver strategy to locate a web
     * element, e.g. you can locate an element by its CSS id, tag name...
     * @link https://www.w3.org/TR/2013/WD-webdriver-20130117/#element-location-strategies
     * @see \WebDriverBy
     *
     * The second must be the value to use with this strategy.
     *
     * Exemples of valid arrays to create a \WebDriverBy instance:
     * ['id', 'login-form'], ['tag name', 'meta'], ['link text', 'logout']
     *
     * WebDriver supports 8 strategies.
     *
     * WebDriver strategy names  |  \WebDriverBy static method names
     * 'class name'                 className()
     * 'css selector'               cssSelector()
     * 'id',                        id()
     * 'name'                       name()
     * 'link text'                  linkText()
     * 'partial link text'          partialLinkText()
     * 'tag name'                   tagName()
     * 'xpath'                      xpath()
     *
     * @param array $location
     * @throws InvalidParamException invalid location strategy
     * @return \WebDriverBy
     */
    public function fromArray(array $location)
    {
        if (2 !== count($location) || !is_string($location[0]) || !is_string($location[1])) {
            throw new InvalidParamException("Invalid param 'location' array.");
        }
        list($strategy, $value) = $location;
        $method;
        switch($strategy) {
            case 'id':                  $method = 'id';             break;
            case 'css selector':        $method = 'cssSelector';    break;
            case 'class name':          $method = 'className';      break;
            case 'name':                $method = 'name';           break;
            case 'xpath':               $method = 'xpath';          break;
            case 'tag name':            $method = 'tagName';        break;
            case 'link text':           $method = 'linkText';       break;
            case 'partial link text':   $method = 'partialLinkText';break;
            default: throw new InvalidParamException("Invalid location strategy '$strategy'.");
        }
        return \WebDriverBy::$method($value);
    }

    /**
     * Return a \WebDriverBy instance - used to find a web element - based on
     * the $location param.
     *
     * A location can be a \WebDriverBy instance which is returned as is.
     * If a string, it's assumed to be the alias of a \WebDriverBy instance
     * stored in the @see $map property.
     * If an array @see fromArray()
     *
     *
     * @param \WebDriverBy|string|array $location
     * @return \WebDriverBy
     * @throws InvalidParamException
     */
    public function resolve($location)
    {
        if ($location instanceof \WebDriverBy) {
            return $location;
        } else if (is_string($location)) {
            return $this->get($location);
        } else if (is_array($location)) {
            return $this->fromArray($location);
        } else {
            throw new InvalidParamException("Location must be an instance of
                \WebDriverBy, a string or an array.");
        }
    }

    /**
     * Return a readable version of the location.
     *
     * @param \WebDriverBy|string|array $location @see resolve()
     * @return string
     * @throws InvalidParamException $location param is not of the expected type
     */
    public function toString($location)
    {
        if ($location instanceof \WebDriverBy) {
            return $location->getMechanism() . ':' . $location->getValue();
        } else if (is_string($location)) {
            return $this->get($location);
        } else if (is_array($location)) {
            return $this->fromArray($location);
        } else {
            throw new InvalidParamException("Location must be an instance of
                \WebDriverBy, a string or an array.");
        }
    }
}
