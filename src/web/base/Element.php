<?php
namespace pyd\testkit\web\base;

use pyd\testkit\AssertionMessage;

/**
 * Base class - with basic features - for web elements.
 *
 * @see \pyd\testkit\web\Element for an "advanced" version, with elements finder...
 * @see \pyd\testkit\web\elements\ for specialized web elements
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Element extends \yii\base\Object
{
    /**
     * @var string the ID used by selenium to identify this web element
     */
    private $ID;
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $driver;

    /**
     * @param string $elementID @see $ID
     * @param \pyd\testkit\web\Driver $webDriver
     * @param array $config
     */
    public function __construct($elementID, \pyd\testkit\web\Driver $webDriver, array $config = [])
    {
        $this->ID = $elementID;
        $this->driver = $webDriver;
        parent::__construct($config);
    }

    /**
     * Create an object of this web element but with another class.
     *
     * You have an object of this class and want to access advanced|specific
     * features:
     * $form->countries->getAs('\pyd\testkit\web\elements\Select')->selectByValue('25');
     *
     * @param string|array $type @see \Yii::createObject
     * @param array $params
     * @return pyd\testkit\web\base\Element a child class
     */
    public function getAs($type, array $params = [])
    {
        array_unshift($params, $this->ID, $this->driver);
        return \Yii::createObject($type, $params);
    }

    /**
     * Return the ID used by selenium to identify this web element.
     *
     * @return string @see $id
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * Get the value of an attribute.
     *
     * If the requested attribute is not present in the tag, this method will
     * return null.
     *
     * If the requested attribute is a boolean one e.g. 'hidden', and is present
     * in the tag, whatever it's value, the string "true" will be returned.
     *
     * @link https://w3c.github.io/webdriver/webdriver-spec.html#dfn-get-element-attribute
     *
     * @param string $name The name of the attribute.
     * @return string|null The value of the attribute.
     */
    public function getAttribute($name)
    {
        return $this->execute(\DriverCommand::GET_ELEMENT_ATTRIBUTE, [':name' =>$name]);
    }

    /**
     * Set the value of an attribute.
     *
     * If the attribute does not already exist, it will be added.
     *
     * @todo investigate element.attribute = value vs element.setAttribute(value)
     *
     * @param string $name attribute name
     * @param string $value attribute value
     * @return \pyd\testkit\web\base\Element or subclass
     */
    public function setAttribute($name, $value)
    {
        $script = "arguments[0].$name='$value';";
        $args = [['ELEMENT' => $this->getId()]];
        $this->driver->executeScript($script, $args);
        return $this;
    }

    /**
     * Get the value of a CSS property.
     *
     * @param string $name  name of the CSS property, not the JavaScript property
     * name (e.g. background-color instead of backgroundColor).
     *
     * @return string the value
     */
    public function getCssProperty($name)
    {
        return $this->execute(\DriverCommand::GET_ELEMENT_VALUE_OF_CSS_PROPERTY, [':propertyName' => $name]);
    }

    /**
     * Set the value of a CSS property.
     *
     * @param string $name  name of the CSS property, not the JavaScript property
     * name (e.g. background-color instead of backgroundColor).
     * @param string $value property value
     * @return \pyd\testkit\web\base\Element
     */
    public function setCssProperty($name, $value)
    {
        $script = "arguments[0].style='$name:$value;'";
        $args = [['ELEMENT' => $this->getId()]];
        $this->driver->executeScript($script, $args);
        return $this;
    }

    /**
     * @return string this element tag name
     */
    public function getTagName()
    {
        return  $this->execute(\DriverCommand::GET_ELEMENT_TAG_NAME);
    }

    /**
     * @return string the visible text between this element tags, including
     * sub-elements, without any leading or trailing whitespace.
     */
    public function getText()
    {
        return $this->execute(\DriverCommand::GET_ELEMENT_TEXT);
    }

    /**
     * @return bool this element is currently displayed
     */
    public function isDisplayed()
    {
        if ($this->execute(\DriverCommand::IS_ELEMENT_DISPLAYED)) {
            AssertionMessage::set('Element is displayed.');
            return true;
        } else {
            AssertionMessage::set('Element is not displayed.');
            return false;
        }
    }

    /**
     * @return bool this element is selected. This method applies on option,
     * checkbox and radio elements.
     */
    public function isSelected()
    {
        if ($this->execute(\DriverCommand::IS_ELEMENT_SELECTED)) {
            AssertionMessage::set('Element is selected.');
            return true;
        } else {
            AssertionMessage::set('Element is not selected.');
            return false;
        }
    }

    /**
     * @param Element $other
     * @return boolean this element equals the $other
     */
    public function equals(Element $other) {
        return $this->execute(\DriverCommand::ELEMENT_EQUALS, [':other' => $other->getId()]);
    }

    /**
     * Click on this element.
     *
     * @return pyd\testkit\web\base\Element
     */
    public function click() {
        $this->execute(\DriverCommand::CLICK_ELEMENT);
        return $this;
    }

    /**
     * @return boolean this element has focus i.e. is the active element
     */
    public function hasFocus()
    {
        if ($this->getID() === $this->driver->getElementFinder()->getActiveElementId()) {
            AssertionMessage::set("Element has focus.");
            return true;
        } else {
            AssertionMessage::set("Element does not have focus.");
            return false;
        }
    }

    /**
     * Simulate typing into an element, which may set - or add to if value is
     * not empty - its value.
     *
     * @param string $value
     * @return pyd\testkit\web\base\Element
     */
    public function sendKeys($value)
    {
      $this->execute(\DriverCommand::SEND_KEYS_TO_ELEMENT, ['value' => \WebDriverKeys::encode($value)]);
    }

    /**
     * Remove content of text input or a textarea.
     *
     * An InvalidElementStateException is raised if the element is not
     * 'user-editable'.
     *
     * This method sets the 'value' attribute to '' and fires an onchange event.
     * @link https://github.com/SeleniumHQ/selenium/blob/master/javascript/atoms/action.js
     *
     * @return pyd\testkit\web\base\Element
     */
    public function clear()
    {
        $this->execute(\DriverCommand::CLEAR_ELEMENT, []);
        return $this;
    }

    /**
     * Execute a selenium command related to this element.
     *
     * @param string $command command name
     * @param array $params command params
     * @return \WebDriverResponse
     */
    protected function execute($command, $params = [])
    {
        $params[':id'] = $this->ID;
        return $this->driver->execute($command, $params);
    }
}
