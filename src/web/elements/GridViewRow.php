<?php
namespace pyd\testkit\web\elements;

/**
 * This element represent a row in a grid view table.
 *
 * @property \pyd\testkit\base\Element $deleteIcon
 * @property \pyd\testkit\base\Element $viewIcon
 * @property \pyd\testkit\base\Element $updateIcon
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class GridViewRow extends \pyd\testkit\web\Element
{
    protected function initLocators()
    {
        $this->addLocator('deletionLink', \WebDriverBy::className('glyphicon-trash'));
        $this->addLocator('viewLink', \WebDriverBy::className('glyphicon-eye-open'));
        $this->addLocator('updateLink', \WebDriverBy::className('glyphicon-pencil'));
    }
}