<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\AssertionMessage;

/**
 * This page is displayed when the csrf validation fails for a request.
 *
 * @see yii\web\Controller::beforeAction()
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class CsrfValidationPage extends Page
{
    /**
     * Verify that the current page is a 'csrf validation' exception page.
     *
     * @return boolean
     */
    public function isDisplayed()
    {
        if (parent::isDisplayed()) {

echo "\nexception message: " . $this->getMessage();
echo "\nexpected message: " . $this->getReferenceText();

            if (false !== strpos($this->getMessage(), $this->getReferenceText())) {
                AssertionMessage::set("This is a CSRF validation Exception page.");
                return true;
            } else {
                AssertionMessage::set("This is not a CSRF validation Exception page.");
                return false;
            }

        } else {
            return false;
        }
    }

    /**
     * Return the message related to a 'csrf validation' exception page.
     *
     * @return string
     */
    protected function getReferenceText()
    {
        return \Yii::t('yii', 'Unable to verify your data submission.');
    }
}
