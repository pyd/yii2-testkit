<?php
namespace pyd\testkit\web\elements;

/**
 * A breadcrumb element.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Breadcrumb extends \pyd\testkit\web\Element
{
    /**
     * @var array \pyd\testkit\functional\Element
     */
    protected $items;

    protected function initLocators()
    {
        parent::initLocators();
        $this->locator->add('items', \WebDriverBy::tagName('li'));
    }

    /**
     * @return array \pyd\testkit\functional\Element
     */
    public function getItems()
    {
        if (null === $this->items) {
            $this->items = $this->findElements('items');
        }
        return $this->items;
    }

    public function hasLabel($label)
    {
        foreach ($this->getItems() as $item) {
            if (0 === strcasecmp($label, $item->getText())) return true;
        }
        return false;
    }

    public function labelContainsText($texte)
    {
        foreach ($this->getItems() as $item) {
            if (false !== strpos($item->getText(), $texte)) return true;
        }
        return false;
    }
}
