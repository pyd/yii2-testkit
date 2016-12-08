<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\AssertionMessage;

/**
 * When a yii\web\Action parameter is missing, a yii\web\BadRequestHttpException
 * is thrown.
 * This class represents the page displayed by the framework - in test mode - in
 * such a case. This is a minimalistic page with a generic message and a list
 * of missing parameters. This is usefull to verify which parameter is required
 * by an action.
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
     * Compare missing parameters listed by the exception message to the
     * expected ones.
     *
     * @param array $params parameter names that should be listed as missing by
     * the exception message
     * @return boolean true only if expected ones === listed ones
     */
    public function missingParametersAre(array $params)
    {
        $paramsFound = $this->getMissingParameters();

        if ($params == $paramsFound) {
            $this->assertionMessage = "Missing parameters are [" .  implode(', ', $params). "].";
            return true;
        }

        $unexpectedFound = array_diff($paramsFound, $params);
        $expectedNotFound = array_diff($params, $paramsFound);

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
     * Extract missing parameter names listed by the exception message.
     *
     * @return array
     */
    public function getMissingParameters()
    {
        $baseMessage = $this->getMissingParametersBaseMessage();
        $paramsStartPos = strpos($this->getMessage(), $baseMessage) + (strlen($baseMessage));
        $params = substr($this->getMessage(), $paramsStartPos);
        return explode(', ', $params);
    }

    /**
     * @return string base of the exception message
     */
    protected function getMissingParametersBaseMessage()
    {
        return \Yii::t('yii', 'Missing required parameters: {params}', ['params' => '']);
    }
}
