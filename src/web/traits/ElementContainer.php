<?php
namespace pyd\testkit\web\traits;

use pyd\testkit\web\element\Helper as ElementHelper;
use pyd\testkit\AssertionMessage;

/**
 * This trait provides tools to manage web elements of a container.
 *
 * A container can be a page or an element.
 *
 * @method public findElement($locator, $reference) Get the first web element
 * matching the locator param
 * @method public findElements($locator, $reference) Get all web elements
 * matching the locator param
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
trait ElementContainer
{
    use ElementFinder;
    use LocatorAlias;

    /**
     * If $name is a locator alias, a web element - or an array of web elements -
     * will be returned.
     *
     * @param string $name
     * @return mixed
     * @throws \NoSuchElementException
     */
    public function __get($name)
    {
        if ($this->hasLocator($name)) {
            $elements = $this->findElements($name);
            switch (count($elements)) {
                case 0:
                    throw new \NoSuchElementException("No element matching alias '$name' was found.");
                case 1:
                    return $elements[0];
                    break;
                default :
                    return $elements;
            }
        }
        parent::__get($name);
    }

    /**
     * If $name is a location alias, a web element - or an array of web elements -
     * will be returned. The type of the web element object depends on the argument:
     * <code>
     * // assuming LoginPage has a 'form' location
     * $form = $loginPage->form();                                              // $form is an instance of Element
     * $form = $loginPage->form(\pyd\testkit\containers\Form::className());     // $form is an instance of Form
     * </code>
     *
     * @param string $name
     * @param array $arguments
     * @return array|element
     * @throws \NoSuchElementException
     * @throws \yii\base\InvalidCallException
     */
    public function __call($name, $arguments)
    {
        if ($this->hasLocator($name)) {
            $elements = $this->findElements($name, $arguments[0]);
            switch (count($elements)) {
                case 0:
                    throw new \NoSuchElementException("No element matching alias '$name' was found.");
                case 1:
                    return $elements[0];
                    break;
                default :
                    return $elements;
            }
        }
        throw new \yii\base\InvalidCallException("Unknown method " . get_class($this) . "::$name().");
    }

    /**
     * Verify if a web element is present (visible or not).
     *
     * @todo this could be done with a call to findElements with a finder that store
     * created elements?
     *
     * @param \WebDriverby|array|string $locator {@see resolveLocator}. String
     * format is only usable with {@link ElementLocatorStorage::resolveLocator}.
     * @return boolean
     */
    public function hasElement($locator)
    {
        $by = $this->resolveLocator($locator);
        try {
            $this->findElementId($by);
            AssertionMessage::set('Element ' . ElementHelper::byToString($by) . ' is present.');
            return true;
        } catch (\NoSuchElementException $e) {
            AssertionMessage::set('Element ' . ElementHelper::byToString($by) . ' is not present.');
            return false;
        }
    }

    /**
     * Verify if web elements - a collection - is present (visible or not).
     *
     *
     * @param \WebDriverby|array|string $locator {@see resolveLocator}
     * @param false|integer $number number of expected elements
     * - if false, it returns true if more than one element is present
     * - if integer, it returns true if its equal to web elements count
     * @return type
     */
    public function hasElements($locator, $number = false)
    {
        $by = $this->resolveLocator($locator);
        $byString = ElementHelper::byToString($by);
        $count = count($this->findElementIds($by));
        if (false === $number) {
            AssertionMessage::set("$count element(s) found for locator $byString.");
            return $count > 1;
        } else {
            AssertionMessage::set("$count/$number elements found for locator $byString.");
            return $count === $number;
        }
    }

    /**
     * Wait for an element to be present - displayed or not.
     *
     * @param \WebDriverBy|string|array $locator {@link LocationManager::createWebDriverBy}
     * @param integer $timeout (seconds) how long to wait for the element to be present
     * @param integer $interval (milliseconds) check condition every $interval ms
     */
    public function waitElementPresent($locator, $timeout = 5, $interval = 500)
    {
        $by = $this->resolveLocator($locator);
        Test::$webDriver->wait($timeout, $interval)->until(
                function ($driver) use ($by) {
                    return $this->findElementId($by);
                },
                'Element ' . ElementHelper::byToString($by) . ' still not present after ' . $timeout . ' sec wait.'
        );
    }

    protected function resolveLocator($locator)
    {
        if ($locator instanceof \WebDriverBy) return $locator;
        if (is_string($locator)) return $this->getLocator($locator);
        if (is_array($locator)) return ElementHelper::createWebDriverByFromArray($locator);
        throw new \InvalidArgumentException("\$locator must be an array, a locator alias - a string - or an instance of \WebDriverBy.");
    }

    /**
     * Use this method to add locators aliases.
     *
     * @note call parent::initLocators to keep parents locators
     */
    protected function initLocators() {}
}
