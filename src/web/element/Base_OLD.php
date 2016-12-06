<?php
namespace pyd\testkit\web\element;

use pyd\testkit\AssertionMessage;

/**
 * Base class for web element.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Base extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;

    /**
     * @var string ID used by selenium server to identify a web element
     */
    protected $id;

    private $_tagName;

    public function __construct(\pyd\testkit\web\Driver $webDriver, $id, $config = array())
    {
        $this->webDriver = $webDriver;
        $this->id = $id;
        parent::__construct($config);
    }

    /**
     * Get the visible text of this element, including sub-elements, without any
     * leading or trailing whitespace.
     *
     * @return string
     */
//    public function getText()
//    {
//        return $this->execute(\DriverCommand::GET_ELEMENT_TEXT);
//    }

    /**
     * Determine whether or not this element is selected.
     *
     * @return bool
     */
    public function isSelected()
    {
        return $this->execute(\DriverCommand::IS_ELEMENT_SELECTED);
    }

    /**
     * Clear TEXTAREA or text INPUT element.
     *
     * @return RemoteWebElement The current instance.
     */
    public function clearField() {
        $this->execute(\DriverCommand::CLEAR_ELEMENT);
        return $this;
    }

    /**
     * Write in TEXTAREA or text INPUT.
     *
     * If $clearField is false and field is not empty $text will be added to the
     * existent.
     *
     * @param string $text what to write in the field
     * @param bool clear field before writing
     * @return \pyd\testkit\web\Element
     */
    public function writeField($text, $clearField = true)
    {
        if ($clearField) $this->clearField();
        $this->execute(\DriverCommand::SEND_KEYS_TO_ELEMENT, ['value' => \WebDriverKeys::encode($text)]);
        return $this;
    }

    public function readField()
    {
        return $this->getAttribute('value');
    }

    /**
     * Test if two element IDs refer to the same DOM element.
     *
     * @param WebDriverElement $other
     * @return bool
     */
//    public function equals(Element $other) {
//        return $this->execute(\DriverCommand::ELEMENT_EQUALS, [':other' => $other->getID()]);
//    }

    /**
     * Click on this element.
     *
     * @return Element
     */
//    public function click() {
//        $this->execute(\DriverCommand::CLICK_ELEMENT);
//        return $this;
//    }

    /**
     * Click on this element and wait for page to be loaded.
     *
     * @param integer $timeout in second
     * @param integer $interval in millisecond
     * @return \pyd\testkit\web\Element
     */
    public function clickAndWaitPageLoaded($timeout = 5, $interval = 500)
    {
        $this->execute(\DriverCommand::CLICK_ELEMENT);
        $this->webDriver->wait($timeout, $interval)->until(
            function(){
                return 'complete' === func_get_arg(0)->executeScript("return document.readyState;");
            },
            "After $timeout seconds waiting, document.readyState still not 'complete'."
        );
            return $this;
    }

    /**
     * Get the value of an attribute.
     *
     * @param string $name The name of the attribute.
     * @return string|null The value of the attribute.
     */
//    public function getAttribute($name)
//    {
//        return $this->execute(\DriverCommand::GET_ELEMENT_ATTRIBUTE, [':name' =>$name]);
//    }

    /**
     * Change the value of a web element attribute e.g. 'href', 'name'...
     * If attribute does not exist, it will be added.
     *
     * @param string $name attribute name
     * @param string $value new value for this attribute
     * @return void
     */
//    public function setAttribute($name, $value)
//    {
//        $script = "arguments[0].$name = arguments[1];";
//        $args = array(array('ELEMENT' => $this->getId()), $value);
//        $this->webDriver->executeScript($script, $args);
//    }

//    public function modifyAttribute($attribute, $value) {
//        $script = "arguments[0].$attribute = arguments[1];";
//        $args = array(array('ELEMENT' => $this->getID()), $value);
//        $this->executor->execute(array('script' => $script, 'args' => $args));
//    }

    /**
     * Get the value of a CSS property.
     *
     * @param string $name  the name of the CSS property to query. it should be
     * specified using the CSS property name, not the JavaScript property name
     * (e.g. background-color instead of backgroundColor).
     *
     * @return string the value
     */
//    public function getCssProperty($name)
//    {
//        return $this->execute(\DriverCommand::GET_ELEMENT_VALUE_OF_CSS_PROPERTY, [':propertyName' => $name]);
//    }

    /**
     * @return string this element tag name
     */
//    public function getTagName()
//    {
//        if (null === $this->_tagName) {
//            $this->_tagName = $this->execute(\DriverCommand::GET_ELEMENT_TAG_NAME);
//        }
//        return $this->_tagName;
//    }

//    public function setTagName($tagName)
//    {
//        $this->_tagName = $tagName;
//    }

    /**
     * @return bool this element is currently displayed
     */
//    public function isDisplayed() {
//        if ($this->execute(\DriverCommand::IS_ELEMENT_DISPLAYED)) {
//            AssertionMessage::set('Element is displayed.');
//            return true;
//        } else {
//            AssertionMessage::set('Element is not displayed.');
//            return false;
//        }
//    }

    /**
     * @note An element is disabled if it can't be activated
     * (e.g. selected, clicked on or accept text input) or accept focus.
     *
     * @return bool
     */
    public function isEnabled() {
        return $this->execute(\DriverCommand::IS_ELEMENT_ENABLED);
    }

    /**
     * @return string this element ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return boolean this element has focus
     */
//    public function hasFocus()
//    {
//        if ($this->equals($this->webDriver->getActiveElement())) {
//            AssertionMessage::set("Element has focus.");
//            return true;
//        } else {
//            AssertionMessage::set("Element does not have focus.");
//            return false;
//        }
//    }

    /**
     * Wait for this element to be visible.
     *
     * @param integer $timeout how long to wait in seconds
     * @param integer $interval verify condition every $interval milliseconds
     */
    public function waitIsVisible($timeout=5, $interval=1000)
    {
        $this->webDriver->wait($timeout, $interval)->until(
            function(){ return $this->isDisplayed(); }
        );
    }

     /**
     * Wait for this element to be hidden.
     *
     * @param integer $timeout how long to wait in seconds
     * @param integer $interval verify condition every $interval milliseconds
     */
    public function waitIsHidden($timeout=5, $interval=1000)
    {
        $this->webDriver->wait($timeout, $interval)->until(
            function(){ return !$this->isDisplayed(); }
        );
    }

    /**
     * Send a command to the selenium server.
     *
     * @param string $command {@link \WebDriverCommand}
     * @param array $params command params
     * @return mixed
     */
    protected function execute($command, array $params = [])
    {
        $params[':id'] = $this->id;
        return $this->webDriver->execute($command, $params);
    }
}
