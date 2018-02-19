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
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class GridView extends \pyd\testkit\web\Element
{
    /**
     * @var array \pyd\testkit\web\elements\GridViewRow table rows
     */
    private $_rows = [];

    protected function initLocators()
    {
        $this->locator->add('summary', \WebDriverBy::className('summary'));
        $this->locator->add('table', \WebDriverBy::tagName('table'));
        $this->locator->add('tableHeaders', \WebDriverBy::tagName('th'));
        $this->locator->add('tableRows', \WebDriverBy::cssSelector('table tbody tr'));
        // empty view message
        $this->locator->add('emptyRow', \WebDriverBy::className('empty'));
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
     * @return array \pyd\testkit\web\elements\GridViewRow
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
     * @return \pyd\testkit\web\elements\GridViewRow
     */
    public function findRowByIndex($index)
    {
        return $this->findRows()[$index];
    }

    /**
     * Find the first row with a cell containing the searched text.
     * 
     * @param string text
     * @return array of \pyd\testkit\web\elements\GridViewRow elements
     */
    public function findRowsByCellText($text)
    {
        $location = \WebDriverBy::xpath("//tr[.//td[text()='$text']]");
        return $this->findElements($location, GridViewRow::className());
    }
    
    /**
     * Find the first row with a cell containing the searched text.
     * 
     * @param string text
     * @return \pyd\testkit\web\elements\GridViewRow
     */
    public function findRowByCellText($text)
    {
        $location = \WebDriverBy::xpath("//tr[.//td[text()='$text']]");
        return $this->findElement($location, GridViewRow::className());
    }

    /**
     * @return \pyd\testkit\web\elements\GridViewSummary
     */
    public function findSummary()
    {
        return $this->findElement('summary', GridViewSummary::className());
    }

    /**
     * Check if the grid has a column with the expected label.
     * 
     * @param string $label
     * @return boolean
     */
    public function hasColumn($label)
    {
        if ($this->hasElement(\WebDriverBy::cssSelector('th[text="'.$label.'"]'))) {
            AssertionMessage::set("Grid does have a column with label '$label'.");
            return true;
        } else {
            AssertionMessage::set("Grid does not have a column with label '$label'.");
            return false;
        }
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
