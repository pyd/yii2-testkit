<?php
namespace pyd\testkit\web\base;

use pyd\testkit\web\elements\Helper;
use pyd\testkit\AssertionMessage;

/**
 * Find web element(s) in the DOM or within a web element.
 * 
 * Finding web element(s) is a 2 steps process:
 * - request webDriver to get ID(s) of web element(s);
 * - create and return web element instance(s) with this ID(s);
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ElementFinder
{
    /**
     * @var \RemoteExecuteMethod to send command to webDriver
     */
    protected $executor;
    
    /**
     * @var \pyd\testkit\web\base\ElementCreator to create web element objects
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
     * Get the ID of the first web element in the DOM that matches the selector.
     * 
     * This ID is used by webDriver to identify a web element.
     * A \NoSuchElementException is thrown if no element matches the selector.
     *
     * @param \WebDriverBy $by element selector
     * @return string web element ID
     */
    public function getID(\WebDriverBy $by)
    {
        $response = $this->executor->execute(\DriverCommand::FIND_ELEMENT,
                 ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        return $response['ELEMENT'];
    }

    /**
     * Get the ID of the first web element that is a child of the target web
     * element and matches the selector.
     * 
     * This ID is used by webDriver to identify a web element.
     * A \NoSuchElementException is thrown if no element matches the selector.
     *
     * @param \WebDriverBy $by element selector
     * @param string $parentElementID ID of the parent element
     * @return string web element ID
     */
    public function getChildID(\WebDriverBy $by, $parentElementID)
    {
        $response = $this->executor->execute(\DriverCommand::FIND_CHILD_ELEMENT,
            [':id' => $parentElementID, 'using' => $by->getMechanism(), 'value' => $by->getValue()]);
        return $response['ELEMENT'];
    }

    /**
     * Get the IDs of all web elements in the DOM that match the selector.
     * 
     * An ID is used by webDriver to identify a web element.
     * An empty array is returned if no element matches the selector.
     * 
     * @param \WebDriverBy $by element selector
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
     * Get the IDs of all web elements that match the selector and are children
     * of the target web element.
     *
     * An ID is used by webDriver to identify a web element.
     * An empty array is returned if no element matches the selector.
     * 
     * @param \WebDriverBy $by element selector
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
     * Get the ID of the web element that has focus.
     *
     * An ID is used by webDriver to identify a web element.
     * 
     * @return string element ID
     */
    public function getActiveElementId()
    {
        $response = $this->executor->execute(\DriverCommand::GET_ACTIVE_ELEMENT);
        return $response['ELEMENT'];
    }

    /**
     * Get the first web element in the DOM that matches the selector.
     *
     * A \NoSuchElementException is thrown if there's no matching element.
     *
     * @param \WebDriverBy $by element selector
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
     * Get all web elements in the DOM that match the selector.
     *
     * @param \WebDriverBy $by element selector
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
     * Get the first web element that matches the selector and is a child of the
     * target web element.
     *
     * A \NoSuchElementException is thrown if there's no matching element.
     *
     * @param \WebDriverBy $by element selector
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
     * Get all web elements that matches the selector and are children of the
     * target web element.
     *
     * @param \WebDriverBy $by element selector
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
     * Check if a web element that matches the selector is present in the DOM.
     *
     * @param \WebDriverBy $by element selector
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
     * Check if a web element that matches the selector is present as a child of
     * the target web element.
     *
     * @param \WebDriverBy $by element selector
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
     * Get the web element that has focus.
     *
     * @return \pyd\testkit\web\base\Element
     */
    public function findActiveElement()
    {
        $elementID = $this->getActiveElementId();
        return $this->creator->create($elementID);
    }
}
