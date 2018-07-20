<?php
namespace pyd\testkit\web\elements;

/**
 * A <table> web element.
 * 
 * @property \pyd\testkit\base\Element $caption table caption
 * @property array $headers table header cells
 * @property array $rows table rows (without header one)
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Table extends \pyd\testkit\web\Element
{
    /**
     * Add locators:
     * - caption;
     * - header cells;
     * - body rows;
     */
    protected function initLocators()
    {
        parent::initLocators();
        $this->locator->add('caption', \WebDriverBy::tagName('caption'));
        $this->locator->add('headers', \WebDriverBy::cssSelector('tr th'));
        $this->locator->add('rows', \WebDriverBy::cssSelector('tbody tr'));
    }

    /**
     * @return array \pyd\testkit\web\base\Element
     */
    public function getHeaders()
    {
        return $this->findElements('headers')->asA(\pyd\testkit\web\Element::className());
    }

    /**
     * @return array header cellls lables
     */
    public function getHeaderLabels()
    {
        $labels = [];
        foreach ($this->getHeaders() as $cell) {
            $labels = $cell->getText();
        }
        return $labels;
    }
}
