<?php
namespace pyd\testkit\web\exceptions;

/**
 * Interface for exception page parser.
 * 
 * The responsability of an exception page parser class is to extract exception
 * data i.e. http code and exception message.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
interface ExceptionPageParserInterface
{
    /**
     * @return boolean browser display an exception page
     */
    public function isDisplayed();
    
    /**
     * @return int the http exception code
     */
    public function getHttpCode();
    
    /**
     * @return string the message of the exception
     */
    public function getMessage();
}
