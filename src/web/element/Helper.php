<?php
namespace pyd\testkit\web\element;

use yii\base\InvalidParamException;

/**
 * Helpers for web elements.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Helper {

    public static function createWebDriverByFromArray(array $locator)
    {
        if (is_string($locator[0]) && method_exists('WebDriverBy', $locator[0]) && is_string($locator[1])) {
            return \WebDriverBy::$locator[0]($locator[1]);
        }
        throw new InvalidParamException("This array ['" .  implode("', '", $locator). "'] is not a valid locator."
                . "\nExpected format is ['WebdriverBy method name', 'value']."
                . "\nMethod names are 'id', 'className', 'cssSelector', 'tagName', 'xpath', 'name', 'linkText', 'partialLinkText'.");
    }

    /**
     * Convert a \WebDriverBy object into a string to identifying a web element
     * in a message.
     *
     * @param \WebDriverBy $by
     * @return string
     */
    public static function byToString(\WebDriverBy $by)
    {
        return '[' . $by->getMechanism() . ':' . $by->getValue() . ']';
    }

    /**
     * Wait for a present element to be visible.
     *
     * @param Element $element
     * @param integer $timeout (seconds( how long to wait for the element to be present
     * @param integer $interval (milliseconds) check condition every $interval ms
     */
    public static function waitElementVisible(Element $element, $timeout=5, $interval=1000)
    {
        Test::$webDriver->wait($timeout, $interval)->until(
                function ($driver) use ($element) {
                    return $element->isDisplayed();
                },
                'Element ' . $this->byToString($element->getBy()) . ' still not present after ' . $timeout . ' sec wait.'
        );
    }

    /**
     * Wait for a present element to be hidden.
     *
     * @param Element $element
     * @param integer $timeout (seconds( how long to wait for the element to be present
     * @param integer $interval (milliseconds) check condition every $interval ms
     */
    public static function waitElementHidden(Element $element, $timeout=5, $interval=1000)
    {
        Test::$webDriver->wait($timeout, $interval)->until(
                function ($driver) use ($element) {
                    return $element->isDisplayed();
                },
                'Element ' . $this->byToString($element->getBy()) . ' still not present after ' . $timeout . ' sec wait.'
        );
    }

    /**
     * Return ther 'name' attribute of each element.
     *
     * If an element does not have this attribute or its value is an empty string,
     * nothing will be returned for this element.
     *
     * @param array $elements
     * @param boolean $removeDuplicates if a 'name' appears several times (e.g.
     * inputs of a radio button list) only one will be returned.
     * @return array name attributes
     */
    public static function getNames(array $elements, $removeDuplicates = false)
    {
        $names = [];
        foreach ($elements as $element) {
            $name = $element->getAttribute('name');
            if ('' !== $name && null !== $name) {
                $names[] = $name;
            }
        }
        return $removeDuplicates ? array_unique($names) : $names;
    }

    /**
     * Return the 'value' attribute of each element.
     *
     * @param array $elements
     * @return array
     */
    public static function getValues(array $elements)
    {
        $values = [];
        foreach ($elements as $element) {
            $value = $element->getAttribute('value');
            if (null !== $value) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * Each element that is not displayed will be removed from the list.
     *
     * @param array $elements
     * @return array displayed elements
     */
    public static function removeHidden(array $elements)
    {
        foreach ($elements as $key => $element) {
            if (!$element->isDisplayed()) {
                unset($elements[$key]);
            }
        }
        return $elements;
    }

    /**
     * Each element that is displayed will be removed from the list.
     *
     * @param array $elements
     * @return array hidden elements
     */
    public static function removeVisible(array $elements)
    {
        foreach ($elements as $key => $element) {
            if ($element->isDisplayed()) {
                unset($elements[$key]);
            }
        }
        return $elements;
    }
}
