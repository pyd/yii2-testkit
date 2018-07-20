<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\Testkit;
use yii\base\InvalidParamException;

/**
 * Factory for exception pages.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ExceptionPageFactory
{
    /**
     * List of exception page classes indexed by aliases.
     * 
     * @var array each key is an alias and each value is an exception page class
     * name 
     */
    protected static $exceptionTypes = [
        'default' => '\pyd\testkit\web\exceptions\Page',
        'missingParameters' => '\pyd\testkit\web\exceptions\MissingParametersPage',
        'methodNotAllowed' => '\pyd\testkit\web\exceptions\MethodNotAllowedPage',
        'csrfValidation' => '\pyd\testkit\web\exceptions\CsrfValidationPage',
    ];
    
    public static $parserClass = '\pyd\testkit\web\exceptions\ExceptionPageDefaultParser';
    
    /**
     * Create an instance of an exception page.
     * 
     * @param string $exceptionType the type of the exception {@see $types}. The
     * 'default' type will create a \pyd\testkit\web\exceptions\Page instance.
     * @return \pyd\testkit\web\exceptions\Page or subclass
     */
    public static function create($exceptionType = 'default')
    {
        if (!is_string($exceptionType) || !array_key_exists($exceptionType, self::$exceptionTypes)) {
            throw new InvalidParamException("No exception page alias named '$exceptionType' exists.");
        }
        $pageClass = self::$exceptionTypes[$exceptionType];
        $page = \Yii::createObject($pageClass, [Testkit::$app->webDriverManager->getDriver()]);
        
        $parser = \Yii::createObject(self::$parserClass, [$page]);
         
        $page->setParser($parser);
        return $page;
    }
}
