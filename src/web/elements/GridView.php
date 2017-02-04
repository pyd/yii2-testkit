<?php
namespace pyd\testkit\web\elements;

use pyd\testkit\AssertionMessage;

/**
 * A grid view element.
 *
 * @property \pyd\testkit\web\Element $summary grid summary
 * @property \pyd\testkit\web\Element $table grid table
 * @property \pyd\testkit\web\Element $tableHeaders grid table headers
 * @property \pyd\testkit\web\Element $tableRows grid table rows
 * @property \pyd\testkit\web\Element $emptyRow grid row displaying 'no result' msg
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class GridView extends \pyd\testkit\web\Element
{
    /**
     * @var array \pyd\testkit\web\elements\GridViewRow table rows
     */
    private $_rows = [];

    protected function initLocators()
    {
        $this->addLocator('summary', \WebDriverBy::className('summary'));
        $this->addLocator('table', \WebDriverBy::tagName('table'));
        $this->addLocator('tableHeaders', \WebDriverBy::tagName('th'));
        $this->addLocator('tableRows', \WebDriverBy::cssSelector('table tbody tr'));
        // empty view message
        $this->addLocator('emptyRow', \WebDriverBy::className('empty'));
    }

    /**
     * Get the table element.
     *
     * @return \pyd\testkit\web\elements\Table
     */
    public function findTable()
    {
        return $this->findElement('table', \pyd\testkit\web\elements\Table::className());
    }

    /**
     * Get table rows.
     *
     * @return array \pyd\testkit\web\Element
     */
    public function findRows()
    {
        if ([] === $this->_rows) {
            $this->_rows = $this->findElements('tableRows', \pyd\testkit\web\elements\GridViewRow::className());
        }
        return $this->_rows;
    }

    /**
     * Get row by its index - starting from 0.
     *
     * @param integer $index
     * @return \pyd\testkit\functional\Element
     */
    public function findRowByIndex($index)
    {
        return $this->findRows()[$index];
    }

    /**
     * Get rows that contain some text.
     *
     * @param string $text
     * @return array \pyd\testkit\web\elements\GridViewRows
     */
    public function findRowsByText($text)
    {
        $rows = [];
        foreach ($this->findRows() as $row) {
            if (false !== strstr($row->getText(), $text)) {
                $rows[] = $row;
            }
        }
        return  $rows;
    }

    /**
     * @return GridViewSummary
     */
    public function findSummary()
    {
        return $this->findElement('summary', GridViewSummary::className());
    }

    /**
     * Get number of rows.
     *
     * @return integer
     */
    public function countRows()
    {
        return count($this->findRows());
    }

    /**
     * Gridview is empty.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        if ($this->hasElement('emptyRow')) {
            AssertionMessage::set("Grid is empty.");
            return true;
        } else {
            AssertionMessage::set("Grid is not empty.");
            return false;
        }
        return $this->hasElement('emptyRow');
    }
}
