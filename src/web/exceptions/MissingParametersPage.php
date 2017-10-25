<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\AssertionMessage;

/**
 * This page is displayed when a request is sent without all the required
 * parameters.
 *
 * @see yii\web\Controller::bindActionParams()
 *
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class MissingParametersPage extends Page
{
    /**
     * @return boolean displayed page is a missing parameters exception page
     */
    public function isDisplayed()
    {
        if (parent::isDisplayed()) {    // this is an exception page

            $baseMessage = $this->getReferenceText();

            if (false !== strpos($this->getMessage(), $baseMessage)) {
                AssertionMessage::set("This is a missing parameters exception page.");
                return true;
            } else {
                AssertionMessage::set("This is not a missing parameters exception page.");
                return false;
            }

        } else {
            return false;
        }
    }

    /**
     * Verify that the missing parameters matches the expected names.
     *
     * Missing parameters are extracted from the exception message.
     *
     * @param array $expected param names
     * @return boolean true missing parameters === expected parameters
     */
    public function missingParametersAre(array $expected)
    {
        $extracted = $this->extractMissingParameterNames();

        if ($expected == $extracted) { return true; }

        $unexpectedFound = array_diff($paramsFound, $paramNames);
        $expectedNotFound = array_diff($paramNames, $paramsFound);

        $assertionMessage = '';
        if ([] !== $unexpectedFound) {
            $assertionMessage = "\nUnexpected missing parameters [" . implode(', ', $unexpectedFound) . "] are listed by the exception page.";
        }
        if ([] !== $expectedNotFound) {
            $assertionMessage .= "\nExpected missing parameters [" . implode(', ', $expectedNotFound) . "] are not listed by the exception page.";
        }
        AssertionMessage::set($assertionMessage);
        return false;
    }

    /**
     * Extract missing parameter names from the exception message.
     *
     * @return array
     */
    public function extractMissingParameterNames()
    {
        $baseMessage = $this->getReferenceText();
        $paramsStartPos = strpos($this->getMessage(), $baseMessage) + (strlen($baseMessage));
        $params = substr($this->getMessage(), $paramsStartPos);
        return explode(', ', $params);
    }

    /**
     * Return the reference of the exception message.
     * 
     * @return string skeleton of the exception message
     */
    protected function getReferenceText()
    {
        return \Yii::t('yii', 'Missing required parameters: {params}', ['params' => '']);
    }
}
