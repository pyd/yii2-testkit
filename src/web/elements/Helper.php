<?php
namespace pyd\testkit\web\elements;

use yii\base\InvalidParamException;

/**
 * Helpers for web elements.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Helper {

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
            /* @var $element \pyd\testkit\web\element\Base */
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
