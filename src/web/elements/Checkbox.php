<?php
namespace pyd\testkit\web\elements;

use pyd\testkit\AssertionMessage;

/**
 * A checkbox element.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Checkbox extends \pyd\testkit\web\Element
{
    protected function initLocators()
    {
        $this->addLocator('label', \WebDriverBy::tagName('label'));
    }

    public function isChecked()
    {
        if ($this->execute(\DriverCommand::IS_ELEMENT_SELECTED)) {
            AssertionMessage::set("Checkbox is checked.");
            return true;
        } else {
            AssertionMessage::set("Checkbox is not checked.");
            return false;
        }
    }
}
