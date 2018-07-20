<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\AssertionMessage;

/**
 * Parser for an exception page which content was generated with the yii default
 * view: views/site/error.
 * 
 * @see \pyd\testkit\web\exceptions\Page
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ExceptionPageDefaultParser implements ExceptionPageParserInterface
{
    /**
     * @var pyd\testkit\web\base\Page
     */
    protected $page;
    /**
     * @var array locator of the container
     */
    protected $containerLocator = ['class name', 'site-error'];
    /**
     * @var array locator of the http code element within the container.
     */
    protected $httpCodeLocator = ['tag name', 'h1'];
    /**
     * @var array locator of the exception message element within the container. 
     */
    protected $messageLocator = ['class name', 'alert'];
    
    /**
     * @param pyd\testkit\web\base\Page $page
     */
    public function __construct(\pyd\testkit\web\base\Page $page)
    {
        $this->page = $page;
    }
    
    /**
     * @return boolean the currently displayed page is an exception page
     */
    public function isDisplayed()
    {
        try {
            $this->getContainer();
            AssertionMessage::set("This is an exception page.");
            return true;
        } catch (\NoSuchElementException $e) {
            AssertionMessage::set("This is not an exception page. Unable to "
                    . "locate the reference element '" . $this->containerLocator [1] . "'.");
            return false;
        }
    }
    
    /**
     * @return integer the http code of the exception page.
     */
    public function getHttpCode()
    {
        $element = $this->getContainer()->findElement($this->httpCodeLocator);
        $text = $element->getText();
        // will return 404 if text is 'Not Found (#404)'
        if (false !== preg_match('`\(#(\d{3})\)`', $text, $matches)) {
            return $matches[1];
        } else {
            throw new \yii\base\Exception("Cannot extract exception page http code from text: '$text");
        }
    }
    
    /**
     * @return string the exception message
     */
    public function getMessage()
    {
        $element = $this->getContainer()->findElement($this->messageLocator);
        return $element->getText();
    }
    
    private $_container;
    
    /**
     * @return \pyd\testkit\web\Element the container of the exception
     * page
     */
    protected function getContainer()
    {
        if (null === $this->_container) {
            
            $this->_container = $this->page->findElement($this->containerLocator, '\pyd\testkit\web\Element');
        }
        return $this->_container;
    }
}
