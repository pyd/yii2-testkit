<?php
namespace pyd\testkit\web\elements;

/**
 * This element represent a row in a grid view table.
 *
 * @property \pyd\testkit\web\base\Element $deleteLink
 * @property \pyd\testkit\web\base\Element $viewLink
 * @property \pyd\testkit\web\base\Element $updateLink
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class GridViewRow extends \pyd\testkit\web\Element
{
    protected function initLocators()
    {
        $this->locator->add('deleteLink', \WebDriverBy::className('glyphicon-trash'));
        $this->locator->add('viewLink', \WebDriverBy::className('glyphicon-eye-open'));
        $this->locator->add('updateLink', \WebDriverBy::className('glyphicon-pencil'));
    }
}
