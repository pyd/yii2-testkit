<?php
namespace pyd\testkit\web\exceptions;

use pyd\testkit\Tests;
use yii\base\InvalidParamException;

/**
 * Create exception page instances.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ExceptionPageFactory
{
    /**
     * @var array each key is an alias and each value is an exception page class
     * name 
     */
    protected static $pageMap = [
        'default' => '\pyd\testkit\web\exceptions\Page',
        'missingParameters' => '\pyd\testkit\web\exceptions\MissingParametersPage',
        'methodNotAllowed' => '\pyd\testkit\web\exceptions\MethodNotAllowedPage',
        'csrfValidation' => '\pyd\testkit\web\exceptions\CsrfValidationPage',
    ];
    
    public static $parserClass = '\pyd\testkit\web\exceptions\ExceptionPageDefaultParser';
    
    /**
     * 
     * @param string $pageAlias {@see $pageMap}
     * @return \pyd\testkit\web\exceptions\Page or subclass
     */
    public static function create($pageAlias = 'default')
    {
        if (!is_string($pageAlias) || !array_key_exists($pageAlias, self::$pageMap)) {
            throw new InvalidParamException("No exception page alias named '$pageAlias' exists.");
        }
        $pageClass = self::$pageMap[$pageAlias];
        $page = \Yii::createObject($pageClass, [Tests::$manager->getWebDriverManager()->getDriver()]);
        
        $parser = \Yii::createObject(self::$parserClass, [$page]);
         
        $page->setParser($parser);
        return $page;
    }
}
