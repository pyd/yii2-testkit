<?php
namespace pyd\testkit\web\elements;

use pyd\testkit\AssertionMessage;
use NoSuchElementException;

/**
 * Yii DetailView web element.
 * 
 * Detail view format is:
 * <table>
 *  <tbody>
 *   <tr>
 *    <th>label</th>
 *    <td>value</td>
 *   </tr>
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class DetailView extends \pyd\testkit\web\Element
{
    /**
     * Get the value of a row identified by its label.
     * 
     * @param string $label row label
     * @return \pyd\testkit\web\base\Element
     * @throws NoSuchElementException
     */
    public function getValueByLabel($label)
    {
        try {
            return $this->findElement(\WebDriverBy::xpath('//th[contains(text(), "' . $label . '")]/following-sibling::td'))->getText();
        } catch (NoSuchElementException $e) {
            throw new NoSuchElementException("Detail view does not have a '$label' label.");
        }
    }
    
    /**
     * Check if the detail view has a row which label matches the provided one.
     * 
     * @param string $label
     * @return boolean
     */
    public function hasLabel($label)
    {
        if ($this->hasElement(\WebDriverBy::xpath('//th[contains(text(), "' . $label . '")]'))) {
            AssertionMessage::set("Detail view has a row named '$label'.");
            return true;
        } else {
            AssertionMessage::set("Detail view does not have a row named '$label'.");
            return false;
        }
    }
}
