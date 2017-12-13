<?php
namespace pyd\testkit\web\elements;

/**
 * Yii DetailView web element.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class DetailView extends \pyd\testkit\web\Element
{
    /**
     * Get the value of a row identified by its label.
     * 
     * @param string $label
     * @return string
     */
    public function getRowValueByLabel($label)
    {
        /*
         * detail view format is:
         * <table>
         *  <tbody>
         *   <tr>
         *    <th>label</th>
         *    <td>value</td>
         *   </tr>
         */
        return $this->findElement(\WebDriverBy::xpath('//th[contains(text(), "' . $label . '")]/following-sibling::td'))->getText();
    }
}
