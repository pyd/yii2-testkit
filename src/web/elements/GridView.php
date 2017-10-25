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
     * Get rows that contain some text.
     *
     * @todo allow $text param as array to pass several words
     *
     * @param string $text
     * @return array \pyd\testkit\web\elements\GridViewRow
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
     * Search for a row that contains the text passed as param.
     *
     * This method will search for the text in all the rows of the grid.
     * If no row contains the text, a \NoSuchElementException exception will be
     * thrown.
     * If one row contains the text, it will be returned.
     * If more than one row contains the text:
     * - an InvalidCallException is thrown by default;
     * - the first occurrence is returned if param $allowMulti is set to true;
     *
     * @param string $text the text to search for
     * @param boolean $allowMulti if more than one row contains the text, the
     * first occurrence will be returned
     * @return \pyd\testkit\web\elements\GridViewRow
     * @throws \NoSuchElementException
     * @throws \yii\base\InvalidCallException
     */
    public function findRowByText($text, $allowMulti = false)
    {
        $rows = $this->findRowsByText($text);
        $count = count($rows);
        if (0 === $count) {
            throw new \NoSuchElementException("There's no row in the grid that contains th text '$text'.");
        } else if (1 === $count || $allowMulti) {
            return $rows[0];
        } else {
            throw new \yii\base\InvalidCallException("More than one row in the grid contains the text '$text'.");
        }
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
