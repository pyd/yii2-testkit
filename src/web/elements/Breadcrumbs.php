<?php
namespace pyd\testkit\web\elements;

/**
 * A breadcrumbs web element.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Breadcrumbs extends \pyd\testkit\web\Element
{
    /**
     * @var array \pyd\testkit\functional\Element
     */
    private $_items;

    /**
     * Add locator:
     * - array: items in the breadcrumbs element;
     */
    protected function initLocators()
    {
        parent::initLocators();
        $this->locator->add('items', \WebDriverBy::tagName('li'));
    }

    /**
     * Find all items.
     * 
     * @return array \pyd\testkit\functional\Element
     */
    public function findItems()
    {
        if (null === $this->_items) {
            $this->_items = $this->findElements('items');
        }
        return $this->_items;
    }

    /**
     * One item has the expected label.
     * 
     * @param string $label
     * @param boolean $caseSensitive 
     * @return boolean
     */
    public function hasLabel($label, $caseSensitive = false)
    {
        $method = $caseSensitive ? 'strcmp' : 'strcasecmp';
        foreach ($this->findItems() as $item) {
            return (0 === $method($label, $item->getText()));
        }
        return false;
    }

    /**
     * One item has a label that contains - may be all label or just a part of
     * it - the expected text.
     * 
     * @param string $texte
     * @return boolean
     */
    public function labelContainsText($texte, $caseSensitive = false)
    {
        $method = $caseSensitive ? 'strpos' : 'stripos';
        foreach ($this->findItems() as $item) {
            return (false !== $method($item->getText(), $texte));
        }
        return false;
    }
    
    /**
     * Get all labels as an array of strings.
     * 
     * @return array
     */
    public function getLabels()
    {
        $labels = [];
        foreach ($this->findItems() as $item) {
            $labels[] = $item->getText();
        }
        return $labels;
    }

}
