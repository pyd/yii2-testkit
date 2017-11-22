<?php
namespace pyd\testkit\web\exceptions;

use Yii;
use yii\base\InvalidCallException;
use yii\web\Response;
use yii\helpers\StringHelper;
use pyd\testkit\Test;
use pyd\testkit\AssertionMessage;
use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;
use yii\web\ServerErrorHttpException;

/**
 * Base class for the exception pages.
 *
 * As exception pages can be customized, the responsability to parse their
 * content - to get exception data - is delegated to the {@see $parser} object.
 * 
 * @see pyd\testkit\web\exceptions\ExceptionPageDefaultParser can handle 'default'
 * exception pages i.e. displayed by the app/views/site/error view.
 * If you use a custom view to render exception pages, you should create a
 * specific parser that implements {@see pyd\testkit\web\exceptions\ExceptionPageParserInterface}.
 * 
 * ```php
 * $exceptionPage = new \pyd\testkit\web\exceptions\Page($this->webDriver);
 * $parser = new \pyd\testkit\web\exceptions\ExceptionPageDefaultParser($exceptionPage);
 * $exceptionPage->setParser($parser);
 * $this->assertTrue($exceptionPage->isDisplayed());
 * ```
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Page extends \pyd\testkit\web\base\Page
{
    /**
     * @var \pyd\testkit\web\exceptions\ExceptionPageParserInterface 
     */
    protected $parser;
    
    /**
     * @see $parser
     * @param \pyd\testkit\web\exceptions\ExceptionPageParserInterface $parser
     */
    public function setParser(ExceptionPageParserInterface $parser)
    {
        $this->parser = $parser;
    }
    
    /**
     * @see $parser
     * @return \pyd\testkit\web\exceptions\ExceptionPageParserInterface
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * This is an exception page displayed in the browser.
     *
     * ```php
     * $this->assertTrue($exceptionPage->isDisplayed(), AssertionMessage::get());
     * ```
     * 
     * @return boolean
     */
    public function isDisplayed()
    {
        return $this->parser->isDisplayed();
    }

    /**
     * Check if the message of the exception page contains some text.
     *
     * ```php
     * $this->assertTrue($exceptionPage->messageContains('some text'), AssertionMessage::get());
     * ```
     * 
     * @param string $text some text
     * @return boolean
     */
    public function messageContains($text)
    {
        $message = $this->getMessage();
        if (false !== strpos($message, $text)) {
            AssertionMessage::set("Exception page message contains '$text' text.");
            return true;
        } else {
            AssertionMessage::set("Exception page message does not contain '$text' text.");
            return false;
        }
    }

    /**
     * Check if the http code of the exception page matches the expected one.
     * 
     * ```php
     * $this->assertTrue($exceptionPage->matchHttpCode(403), AssertionMessage::get());
     * ```
     * 
     * @param integer $httpCode expected code
     * @return boolean
     * @throws InvalidParamException
     */
    public function matchHttpCode($httpCode)
    {
        $actualCode = $this->getHttpCode();
        if ($actualCode == $httpCode) {
            AssertionMessage::set("The exception page http code is $httpCode.");
            return true;
        } else {
            AssertionMessage::set("The exception page http code is not $httpCode but $actualCode.");
            return false;
        }
    }

    /**
     * Get the exception message.
     * 
     * @return string
     */
    public function getMessage()
    {
        return $this->parser->getMessage();
    }
    
    /**
     * Get the http code from the exception page.
     * @return type
     */
    public function getHttpCode()
    {
        return $this->parser->getHttpCode();
    }

    /**
     * Overwrite the parent implementation because an exception page is not
     * meant to be loaded.
     * 
     * @param array $urlParams
     * @param boolean $verifyDisplay
     * @throws InvalidCallException
     */
    public function load(array $urlParams = array(), $verifyDisplay = true)
    {
        throw new InvalidCallException("Cannot load an exception page.");
    }
}
