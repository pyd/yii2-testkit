<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\AssertionMessage;

/**
 * Missing parameter(s) exception page.
 *
 * <code>
 * // verify that an action require an 'id' parameter
 * $exceptionPage = new MissingParametersPage($this->webDriver);
 * $this->webDriver->get($urlWithoutIdParameter);
 * $this->assertTrue($exceptionPage->isDisplayed(), \pyd\testkit\AssertionMessage::get()));
 * $this->assertTrue($pageException->missingParametersAre(['id'], \pyd\testkit\AssertionMessage::get()));
 * </code>
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class MissingParametersPage extends Page
{
    /**
     * @return boolean displayed page is a missing parameters exception page
     */
    public function isDisplayed()
    {
        if (parent::isDisplayed()) {

            $baseMessage = $this->getMissingParametersBaseMessage();

            if (false !== strpos($this->getMessage(), $baseMessage)) {
                AssertionMessage::set("This is a missing parameters exception page.");
                return true;
            } else {
                AssertionMessage::set("This is not a missing parameters exception page.");
            }
        }
        return false;
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
        $baseMessage = $this->getMissingParametersBaseMessage();
        $paramsStartPos = strpos($this->getMessage(), $baseMessage) + (strlen($baseMessage));
        $params = substr($this->getMessage(), $paramsStartPos);
        return explode(', ', $params);
    }

    /**
     * @return string skeleton of the exception message
     */
    protected function getMissingParametersBaseMessage()
    {
        return \Yii::t('yii', 'Missing required parameters: {params}', ['params' => '']);
    }
}
