<?php
namespace pyd\testkit\web\exceptions;

use Yii;
use yii\base\InvalidCallException;
use yii\web\Response;
use yii\helpers\StringHelper;
use pyd\testkit\Test;
use pyd\testkit\AssertionMessage;
use yii\base\InvalidParamException;

/**
 * Base class for exception page.
 *
 * @todo rename this class to Page. Do the same with other *Page classes of this namespace.
 *
 * Note that this class is designed to extract an exception message generated
 * by the {@link yii\base\ErrorHandler::convertExceptionToString} method used in
 * test env.
 * It may not work if you've defined your own ErrorHandler and|or customized
 * error display.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class ExceptionPage extends \pyd\testkit\web\Page
{
    /**
     * @var string the exception message
     */
    private $_message;

    /**
     * An exception page is displayed.
     *
     * This method will also extract the exception message and store it in @see $_message
     *
     * @return boolean
     * @throws Exception
     */
    public function isDisplayed()
    {
        $result = preg_match('`<pre>(?<text>.*)</pre>`', $this->getSource(), $matches);
        if (1 === $result) {
            $this->_message = $matches['text'];
            return true;
            AssertionMessage::set("This is an exception page.");
        } else if (0 === $result) {
            AssertionMessage::set("This is not an exception page.");
            return false;
        } else if (false === $result) {
            throw new Exception("Error while parsing page source with preg_match method.");
        }
    }

    /**
     * Exception message contains some text.
     *
     * @param string $text searched text
     * @return boolean
     */
    public function messageContains($text)
    {
        if (false !== strpos($this->getMessage(), $text)) {
            AssertionMessage::set("Exception page message contains '$text' text.");
            return true;
        } else {
            AssertionMessage::set("Exception page message does not contain '$text' text.");
            return false;
        }
    }

    /**
     * Verify that the text of the exception page corresponds to the expected
     * http code.
     *
     * @see yii\web\response::$httpStatuses
     *
     * @param integer $httpCode expected code
     * @return boolean
     * @throws InvalidParamException
     */
    public function matchHttpCode($httpCode)
    {
        if (isset(Response::$httpStatuses[$httpCode])) {
            $codeAsText = Response::$httpStatuses[$httpCode];
        } else {
            throw new InvalidParamException("Cannot verify http code $httpCode because "
                    . "it's not defined in yii\web\Response::\$httpStatuses");
        }

        if ($this->messageContains($codeAsText)) {
            AssertionMessage::set("This exception page display a '$httpCode - $codeAsText' message.");
            return true;
        } else {
            AssertionMessage::set("This exception page does not display a '$httpCode - $codeAsText' message.");
            AssertionMessage::add("Message is '" .$this->getMessage(). "'.");
            return false;
        }
    }

    /**
     * Get exception message.
     *
     * @return string
     * @throws Exception
     */
    public function getMessage()
    {
        if (null === $this->_message) {

            // isDisplayed() will extract exception message from page source
            if (!$this->isDisplayed()) {
                throw new NotExceptionPageException("Cannot get exception message. This is not an exception page.");
            }
        }
        return $this->_message;
    }

    private $_source;

    public function load(array $urlParams = array(), $verifyDisplay = true)
    {
        throw new InvalidCallException("Cannot load an exception page.");
    }
}
