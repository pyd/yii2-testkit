<?php
namespace pyd\testkit\web\base;

use pyd\testkit\web\elements\Helper;
use pyd\testkit\AssertionMessage;

/**
 * Utility to find web elements and get them as objects.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class ElementFinder
{
    /**
     * @var \RemoteExecuteMethod
     */
    protected $executor;
    /**
     * @var \pyd\testkit\web\base\ElementCreator
     */
    protected $creator;

    /**
     * @param \RemoteExecuteMethod $executor
     */
    public function __construct(\RemoteExecuteMethod $commandExecutor, ElementCreator $elementCreator)
    {
        $this->executor = $commandExecutor;
        $this->creator = $elementCreator;
    }

    /**
     * Return the selenium ID of the first element in the DOM that matches the
     * provided location.
     *
     * A \NoSuchElementException is raised if there's no matching element.
     *
     * @param \WebDriverBy $by target element location
     * @return string element ID
     */
    public function getID(\WebDriverBy $by)
    {
        $response = $this->executor->execute(\DriverCommand::FIND_ELEMENT,
                 ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        return $response['ELEMENT'];
    }

    /**
     * Return the selenium ID of the first element within a web element that
     * matches the provided location.
     *
     * A \NoSuchElementException is raised if there's no matching element.
     *
     * @param \WebDriverBy $by target element location
     * @param string $parentElementID ID of the parent element
     * @return string child element ID
     */
    public function getChildID(\WebDriverBy $by, $parentElementID)
    {
        $response = $this->executor->execute(\DriverCommand::FIND_CHILD_ELEMENT,
            [':id' => $parentElementID, 'using' => $by->getMechanism(), 'value' => $by->getValue()]);
        return $response['ELEMENT'];
    }

    /**
     * Return the selenium IDs of all elements in the DOM that match the
     * provided location.
     *
     * @param \WebDriverBy $by target elements location
     * @return array IDs of elements. An empty array if no match was found.
     */
    public function getIDs(\WebDriverBy $by)
    {
        $ids = [];
        $response = $this->executor->execute(\DriverCommand::FIND_ELEMENTS,
                ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        foreach ($response as $item) {
            $ids[] = $item['ELEMENT'];
        }
        return $ids;
    }

    /**
     * Return the selenium IDs of all elements within a web element that
     * matches the provided location.
     *
     * @param \WebDriverBy $by target elements location
     * @param string $parentElementID ID of the parent element
     * @return array IDs of elements. An empty array if no match was found.
     */
    public function getChildIDs(\WebDriverBy $by, $parentElementID)
    {
        $ids = [];
        $response = $this->executor->execute(\DriverCommand::FIND_CHILD_ELEMENTS,
                [':id' => $parentElementID, 'using' => $by->getMechanism(), 'value' => $by->getValue()]);
        foreach ($response as $item) {
            $ids[] = $item['ELEMENT'];
        }
        return $ids;
    }

    /**
     * Return the selenium ID of the element that has focus.
     *
     * @return string element ID
     */
    public function getActiveElementId()
    {
        $response = $this->executor->execute(\DriverCommand::GET_ACTIVE_ELEMENT);
        return $response['ELEMENT'];
    }

    /**
     * Return an object representing the first element in the DOM that matches
     * the provided location.
     *
     * A \NoSuchElementException is raised if there's no matching element.
     *
     * @param \WebDriverBy $by target element location
     * @param string|array|callable $type a definition of the object to be
     * created @see \Yii::createObject
     * @return \pyd\testkit\web\base\Element or subclass
     */
    public function findElement(\WebDriverBy $by, $type = null)
    {
        $elementID = $this->getID($by);
        return $this->creator->create($elementID, $type);
    }

    /**
     * Return an array of objects representing all elements in the DOM that
     * match the provided location.
     *
     * @param \WebDriverBy $by target elements location
     * @param string|array|callable $type a definition of the objects to be
     * created @see \Yii::createObject
     * @return array of \pyd\testkit\web\base\Element or subclass. An empty
     * array if no match was found.
     */
    public function findElements(\WebDriverBy $by, $type = null)
    {
        $elements = [];
        foreach ($this->getIDs($by) as $id) {
            $elements[] = $this->creator->create($id, $type);
        }
        return $elements;
    }

    /**
     * Return an object representing the first element within a web element that
     * matches the provided location.
     *
     * A \NoSuchElementException is raised if there's no matching element.
     *
     * @param \WebDriverBy $by target element location
     * @param string $parentElementID ID of the parent element
     * @param string|array|callable $type a definition of the object to be
     * created @see \Yii::createObject
     * @return \pyd\testkit\web\base\Element or subclass
     */
    public function findChildElement(\WebDriverBy $by, $parentElementID, $type = null)
    {
        $elementID = $this->getChildID($by, $parentElementID);
        return $this->creator->create($elementID, $type);
    }

    /**
     * Return an array of objects representing all elements within a web element
     * that matches the provided location.
     *
     * @param \WebDriverBy $by target element location
     * @param string $parentElementID ID of the parent element
     * @param string|array|callable $type a definition of the object to be
     * created @see \Yii::createObject
     * @return array of \pyd\testkit\web\base\Element or subclass. An empty
     * array if no match was found.
     */
    public function findChildElements(\WebDriverBy $by, $parentElementID, $type = null)
    {
        $elements = [];
        foreach ($this->getChildIDs($by, $parentElementID) as $id) {
            $elements[] = $this->creator->create($id, $type);
        }
        return $elements;
    }

    /**
     * Verify if an element is present in the DOM (visible or not) that matches
     * the provided location.
     *
     * @param \WebDriverBy $by target element location
     * @return boolean
     */
    public function hasElement(\WebDriverBy $by)
    {
        $byToString = Helper::byToString($by);
        try {
            $this->getID($by);
            AssertionMessage::set("Element $byToString is present.");
            return true;
        } catch (\NoSuchElementException $e) {
            AssertionMessage::set("Element $byToString is not present.");
            return false;
        }
    }

    /**
     * Verify if an element is present within another element (visible or not)
     * that matches the provided location.
     *
     * @param \WebDriverBy $by target element location
     * @param string $parentElementID ID of the parent element
     * @return boolean
     */
    public function hasChildElement(\WebDriverBy $by, $parentElementID)
    {
        $byToString = Helper::byToString($by);
        try {
            $this->getChildID($by, $parentElementID);
            AssertionMessage::set("Element $byToString is present.");
            return true;
        } catch (\NoSuchElementException $e) {
            AssertionMessage::set("Element $byToString is not present.");
            return false;
        }
    }

    /**
     * Return an object representing the element that has focus.
     *
     * @return \pyd\testkit\web\base\Element
     */
    public function findActiveElement()
    {
        $elementID = $this->getActiveElementId();
        return $this->creator->create($elementID);
    }
}
