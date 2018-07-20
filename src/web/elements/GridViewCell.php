<?php
namespace pyd\testkit\web\elements;

/**
 * A grid view cell.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class GridViewCell extends pyd\testkit\web\Element
{
    protected function initLocators()
    {
        parent::initLocators();
        $this->locator->add('link', \WebDriverBy::tagName('a'));
    }

    /**
     * @return boolean this cell contains a link
     */
    public function haslink()
    {
        return $this->hasElement('link');
    }

    /**
     * @return string the url of the link in this cell
     */
    public function getLinkUrl()
    {
        $link = $this->findElement('link');
        return $link->getAttribute('href');
    }
}
