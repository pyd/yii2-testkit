<?php
namespace pyd\testkit\web\elements\bootstrap;

/**
 * Bootstrap 3 list group element.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ListGroup extends \pyd\testkit\web\Element
{
    public function initLocators()
    {
        parent::initLocators();
        $this->getLocator()->add('items', \WebDriverBy::className("list-group-item"));
    }
    
    /**
     * Find list group items.
     * 
     * @return array of \pyd\testkit\web\base\Element
     */
    public function findItems()
    {
        return $this->findElements('items');
    }
}
