<?php
namespace pyd\testkit\web\base;

/**
 *
 * @method string getId() get the selenium ID of the web element
 * @method \pyd\testkit\web\Driver getWebDriver() a web driver instance
 * @method void asA(string $className) return a web element as an object of class
 * $className. $className must extends ElementWrapper
 * @method \WebDriverresponse execute($command, $params = []) send a command to
 * execute to the selenium server
 * @method boolean isDisplayed() this web element is visible on screen
 * @method string getText() get the visible text of this element and its children
 * @method boolean equals(Element $other) check if this element equals the $other
 * @method void click() click on this element
 * @method string getTagName() get this element's tag name
 * @method type getAttribute(string $attributeName) read the value of the
 * element attribute
 * @method type setAttribute(string $attributeName, mixed $attributeValue) write
 * the value of the element attribute
 * @method string getCssProperty($name) get the value of the CSS property named $name
 * @method boolean hasFocus() this element has focus
 * @method void waitIsVisible($timeout=5, $interval=1000) wait for this element
 * to be visible
 * @method void waitIsHidden($timeout=5, $interval=1000) wait for this element
 * to be hidden
 * @method void sendKeys(string $value) sendKeys($value) Simulate typing into an
 * element, which may set its value.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class ElementWrapper extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\web\base\Element the element base object
     */
    protected $element;
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;

    /**
     * @param \pyd\testkit\web\base\Element $element
     * @param array $config
     */
    public function __construct(Element $element, $config = [])
    {
        $this->element = $element;
        $this->webDriver = $element->getWebDriver();
        parent::__construct($config);
    }

    /**
     * Access the @see $element properties.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this->element, $name)) {
            return $this->element->$name;
        }
        return parent::__get($name);
    }

    /**
     * Access the @see $element methods.
     *
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        /** @todo remove is_callable as it will always return true if __call is implemented */
        if (method_exists($this->element, $name) && is_callable([$this->element, $name])) {
            return call_user_func_array([$this->element, $name], $params);
        }
        return parent::__call($name, $params);
    }
}
