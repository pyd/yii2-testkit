<?php
namespace pyd\testkit\web\elements;

use yii\base\InvalidParamException;
use pyd\testkit\AssertionMessage;

/**
 * A radio button list element as a container.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class RadioButtonList extends \pyd\testkit\web\Element
{
    /**
     * List items. Each item is a <label> element containing an
     * input and the label text.
     *
     * @var \pyd\testkit\functional\Element
     */
    private $_items;

    /**
     * Return all items of the list as <label> elements.
     *
     * @return array
     */
    protected function getItems()
    {
        if (null === $this->_items) {
            $this->_items = $this->findElements(\WebDriverBy::tagName('label'));
        }
        return $this->_items;
    }

    /**
     * Refresh list items.
     */
    public function refresh()
    {
        $this->_items = $this->getItems();
    }

    /**
     * Return an input by it's label text.
     *
     * @param string $label
     * @return \pyd\testkit\functional\Element
     */
    public function getButtonByLabel($label)
    {
        $xpath = ".//input[normalize-space(following-sibling::text())='$label']";
        return $this->findElement(\WebDriverBy::xpath($xpath));
    }

    /**
     * Return an input by it's value.
     *
     * @param string $value
     * @return \pyd\testkit\functional\Element
     */
    public function getButtonByValue($value)
    {
        $xpath = ".//input[@value='$value']";
        return $this->findElement(\WebDriverBy::xpath($xpath));
    }

    /**
     * An input of the list is selected.
     *
     * @return boolean
     */
    public function hasSelectedButton()
    {
        foreach ($this->getItems() as $item) {
            if ($button->findElement(\WebDriverBy::tagName('input'))->isSelected()) {
                AssertionMessage::set("There is a selected button in the radio button list.");
                return true;
            }
        }
        AssertionMessage::set("There is no selected button in the radio button list.");
        return false;
    }
}
