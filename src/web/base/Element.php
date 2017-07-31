<?php
namespace pyd\testkit\web\base;

use pyd\testkit\AssertionMessage;

/**
 * Web element base class.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Element extends \yii\base\Object
{
    /**
     * @var string ID used by selenium server to identify a web element
     */
    protected $id;
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;

    /**
     *
     * @param \RemoteWebDriver $webDriver
     * @param string $id @see $id
     * @param array $config
     */
    public function __construct(\RemoteWebDriver $webDriver, $id, $config = [])
    {
        $this->id = $id;
        $this->webDriver = $webDriver;
        parent::__construct($config);
    }

    /**
     * @return string @see $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \pyd\testkit\web\Driver
     */
    public function getWebDriver()
    {
        return $this->webDriver;
    }

    /**
     * Create a @see pyd\testkit\web\base\ElementWrapper instance of $className
     * based on this element.
     *
     * @param string|array|callable $objectType the object type
     * @see \yii\BaseYii::createObject
     * @param array $objectParams the constructor parameters
     * @see \yii\BaseYii::createObject
     * @return \pyd\testkit\web\base\ElementWrapper
     */
    public function asA($objectType, array $objectParams = [])
    {
        array_unshift($objectParams, $this);
        return \Yii::createObject($objectType, $objectParams);
    }

    /**
     * Execute a webdriver command.
     *
     * @param string $command selenium command
     * @param array $params command parameters
     * @return \WebDriverResponse
     */
    public function execute($command, $params = [])
    {
        $params[':id'] = $this->id;
        return $this->webDriver->execute($command, $params);
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
     * @return bool this element is selected
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
     * @return string the visible text of this element, including sub-elements,
     * without any leading or trailing whitespace.
     */
    public function getText()
    {
        return $this->execute(\DriverCommand::GET_ELEMENT_TEXT);
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
     * @return pyd\testkit\web\base\Element
     */
    public function click() {
        $this->execute(\DriverCommand::CLICK_ELEMENT);
        return $this;
    }

    private $tagName;

    /**
     * @return string this element tag name
     */
    public function getTagName()
    {
        if (null === $this->tagName) {
            $this->tagName = $this->execute(\DriverCommand::GET_ELEMENT_TAG_NAME);
        }
        return $this->tagName;
    }

    /**
     * Get the value of an attribute for this element.
     * @param string $name The name of the attribute.
     * @return string|null The value of the attribute.
     */
    public function getAttribute($name)
    {
        return $this->execute(\DriverCommand::GET_ELEMENT_ATTRIBUTE, [':name' =>$name]);
    }

    /**
     * Set the value of an attribute for this element. If attribute does not
     * exist, it will be added.
     * @param string $name attribute name
     * @param string $value new value for this attribute
     * @return void
     */
    public function setAttribute($name, $value)
    {
        $script = "arguments[0].$name = arguments[1];";
        $args = array(array('ELEMENT' => $this->getId()), $value);
        $this->webDriver->executeScript($script, $args);
    }

    /**
     * Get the value of a CSS property for this element.
     * @param string $name  the name of the CSS property to query. it should be
     * specified using the CSS property name, not the JavaScript property name
     * (e.g. background-color instead of backgroundColor).
     *
     * @return string the value
     */
    public function getCssProperty($name)
    {
        return $this->execute(\DriverCommand::GET_ELEMENT_VALUE_OF_CSS_PROPERTY, [':propertyName' => $name]);
    }

    /**
     * @return boolean this element has focus
     */
    public function hasFocus()
    {
        if ($this->equals($this->webDriver->getActiveElement())) {
            AssertionMessage::set("Element has focus.");
            return true;
        } else {
            AssertionMessage::set("Element does not have focus.");
            return false;
        }
    }

    /**
     * Wait for this element to be visible.
     *
     * @param integer $timeout how long to wait in seconds
     * @param integer $interval verify condition every $interval milliseconds
     * @return pyd\testkit\web\base\Element
     */
    public function waitIsVisible($timeout=5, $interval=1000)
    {
        $this->webDriver->wait($timeout, $interval)->until(
            function(){ return $this->isDisplayed(); }
        );
        return $this;
    }

    /**
     * Wait for this element to be hidden.
     *
     * @param integer $timeout how long to wait in seconds
     * @param integer $interval verify condition every $interval milliseconds
     * @return pyd\testkit\web\base\Element
     */
    public function waitIsHidden($timeout=5, $interval=1000)
    {
        $this->webDriver->wait($timeout, $interval)->until(
            function(){ return !$this->isDisplayed(); }
        );
        return $this;
    }

    /**
     * Simulate typing into an element, which may set its value.
     *
     * @param string $value
     * @return pyd\testkit\web\base\Element
     */
    public function sendKeys($value)
    {
      $this->execute(\DriverCommand::SEND_KEYS_TO_ELEMENT, ['value' => \WebDriverKeys::encode($value)]);
    }

    /**
     * Clear this element value if it's a text input or a textarea.
     *
     * @return pyd\testkit\web\base\Element
     */
    public function clear()
    {
        $this->execute(\DriverCommand::CLEAR_ELEMENT, []);
        return $this;
    }
}
