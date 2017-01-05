<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\AssertionMessage;

/**
 * An instance of this class represents the exception page displayed by the
 * Yii2 framework when a yii\web\Action parameter is missing (in test mode!).
 *
 * In such a case, a a yii\web\BadRequestHttpException is thrown. The page
 * contains a generic message and a list of missing parameters.
 *
 * <code>
 * // in a test method, verify that the 'id' parameter is required by a controller action
 * $exceptionPage = new MissingParametersExceptionPage($this->webDriver);
 * $this->webDriver->get($urlWithoutIdParameter);
 * $this->assertTrue($exceptionPage->isDisplayed(), \pyd\testkit\AssertionMessage::get()));
 * $this->assertTrue($pageException->missingParametersAre(['id'], \pyd\testkit\AssertionMessage::get()));
 * </code>
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class MissingParametersExceptionPage extends ExceptionPage
{
    /**
     * @return boolean the currently displayed page is a missing parameter
     * exception page
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
     * Verify that the missing parameters listed by the exception message match
     * the expected ones.
     *
     * @param array $paramNames param names
     * @return boolean true missing parameters === expected parameters
     */
    public function missingParametersAre(array $paramNames)
    {
        $paramsFound = $this->extractMissingParameterNames();

        if ($paramNames == $paramsFound) { return true; }

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
