<?php
namespace pyd\testkit\web\elements;

/**
 * This element represent a row in a grid view table.
 *
 * @property \pyd\testkit\web\base\Element $deletionLink
 * @property \pyd\testkit\web\base\Element $viewLink
 * @property \pyd\testkit\web\base\Element $updateLink
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
