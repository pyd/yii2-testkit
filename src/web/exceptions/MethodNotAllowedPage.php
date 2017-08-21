<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\AssertionMessage;

/**
 * This page is displayed when a request is send with an unauthorized method
 * e.g. a request is sent with the GET method but only POST is allowed.
 *
 * @see yii\filters\VerbFilter::beforeAction()
 * @see yii\web\MethodNotAllowedHttpException
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class MethodNotAllowedPage extends Page
{
    /**
     * Verify that the current page is a 'method not allowed' exception page.
     *
     * @return boolean
     */
    public function isDisplayed()
    {
        if (parent::isDisplayed()) {

            if (false !== strpos($this->getMessage(), $this->getExpectedText())) {
                AssertionMessage::set("This is a Not Allowed Exception page.");
                return true;
            } else {
                AssertionMessage::set("This is not a Not Allowed Exception page.");
                return false;
            }

        } else {
            return false;
        }
    }

    /**
     * Extract the allowed method names from the exception message.
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $message = $this->getMessage();
        $sub = substr($message, strrpos($message, ':') + 1);
        $sub = rtrim($sub, '.');
        $sub = trim($sub);
        return explode(',', $sub);
    }

    /**
     * Return some text that should be present in the exception message to
     * identify this page as a 'method not allowed' exception page.
     *
     * @return string
     */
    public function getExpectedText()
    {
        return "Method Not Allowed";
    }
}
