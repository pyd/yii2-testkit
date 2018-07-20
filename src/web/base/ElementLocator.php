<?php
namespace pyd\testkit\web\base;

use yii\base\InvalidParamException;

/**
 * Manage a collection of web element locators for a 'parent' web element.
 * 
 * A locator is an alias that points to a {@see \WebDriverBy} instance.
 * 
 * ```php
 * // add a 'loginForm' alias pointing to the login form web element
 * $loginPage->getLocator()->add('loginForm', \WebDriverBy::id('login-form'));
 * // later, use the alias to get the web element instance
 * $this->assertTrue($loginPage->loginForm->isDisplayed());
 * ```
 * Note that a locator can be added using a {@see \WebDriverBy} instance or an
 * array {@see fromArray}.
 * ```php
 * $loginPage->getLocator()->add('loginForm', ['id' => 'login-form']);
 * ```
 * 
 * @see \WebDriverBy the locator instance
 * @see \pyd\testkit\web\Element::initLocators to define locators for a web
 * element class
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ElementLocator
{
    /**
     * @var array of {@see \WebDriverBy} instances indexed by aliases
     */
    private $map = [];

    /**
     * Add a \WebDriverby instance indexed by an alias to the {@see $map}
     * property.
     *
     * @param string $alias alias of the location
     * @param \WebDriverBy|array $location if an array {@see fromArray}
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
     * Check if an alias exists in the {@see $map} property.
     *
     * @param string $alias
     * @return boolean
     */
    public function aliasExists($alias)
    {
        return isset($this->map[$alias]);
    }

    /**
     * Get a locator - a {@see \WebDriverBy} instance - by its alias.
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
     * Get all locators.
     * 
     * @return array all \WebDriver
     */
    public function getAll()
    {
        return $this->map;
    }

    /**
     * Clear all locators.
     */
    public function clear()
    {
        $this->map = [];
    }

    /**
     * Create a locator - a {@see \WebDriverBy} instance - from an array.
     *
     * The first item must be the a selector strategy and the second the value
     * to be used with this strategy e.g. ['id', 'login-form'],
     * ['tag name', 'meta'], ['link text', 'logout'].
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
     * Resolve a location to a {@see \WebDriverBy} instance.
     *
     * @param \WebDriverBy|string|array $location if a string it must be a
     * selector alias {@see $map}. If an array it must contain a strategy and
     * a value {@see fromArray}.
     * @return \WebDriverBy
     * @throws InvalidParamException location param is not valid
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
     * Return a printable version of a location - to be used in exception msg.
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
