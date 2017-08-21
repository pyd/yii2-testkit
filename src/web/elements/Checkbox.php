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
        $this->locator->add('label', \WebDriverBy::tagName('label'));
    }

    /**
     * @return boolean checkbox is checked
     */
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

    /**
     * Set checkbox checked.
     */
    public function check()
    {
        if (!$this->isChecked()) $this->click();
    }

    /**
     * Set checkbox unchecked.
     */
    public function uncheck()
    {
        if ($this->isChecked()) $this->click();
    }
}
