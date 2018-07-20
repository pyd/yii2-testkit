<?php
namespace pyd\testkit\web\elements\bootstrap;

/**
 * Bootstrap 3 'nav' element.
 * 
 * @property-read array $items menu items
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Nav extends \pyd\testkit\web\Element
{
    protected function initLocators()
    {
        parent::initLocators();
        $this->locator->add('items', \WebDriverBy::tagName('li'));
    }
    
    /**
     * Get all items labels.
     * 
     * @return array
     */
    public function getLabels()
    {
        $labels = [];
        foreach ($this->findElements(\WebDriverBy::tagName('li')) as $item) {
            $labels[] = $item->getText();
        }
        return $labels;
    }
}
