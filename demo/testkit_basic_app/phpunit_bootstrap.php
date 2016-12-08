<?php
/**
 * @license see the yii2-testkit/LICENSE file.
 */

/**
 * phpunit bootstrap file - basic template application
 *
 * @todo adapt $appBasePath, $baseUrl & $testkitPath
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */

// /path/to/your/basic/template/application/directory
$appBasePath = dirname(dirname(__DIR__));

// your basic application url without ending slash
$baseUrl = 'http://www.yii2-basic.lo';                                          // to adapt

// /path/to/your/testkit/directory
$testkitPath = '/var/www/lib/pyd/yii2-testkit';                                 // to adapt

// /path/to/your/tests/directory
$testsPath = $appBasePath . '/tests/testkit_basic_app';

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require_once($appBasePath . '/vendor/autoload.php');
require_once($appBasePath . '/vendor/yiisoft/yii2/Yii.php');

// testkit autoload
require_once($testkitPath . '/vendor/autoload.php');

// register tests namespace
Yii::setAlias('@tests', $testsPath);

// configuration for all tests
$environmentConfig = [

    // each key must be a path to a directory where you want to apply the value
    // as a configuration, including in sub directories.
    'byPath' => [

        // this config will apply to all tests because all test cases files
        // are located under $testPath
        $testsPath => [

            // $SERVER variables
            'serverVars' => [
                'SERVER_NAME' => $baseUrl . '/',
                'SCRIPT_NAME' => $baseUrl . '/index-test.php',
                'SCRIPT_FILENAME' => $appBasePath . '/public/index-test.php',
            ],

            // your app bootstrap files. None is used in the basic app template
            'bootstrapFiles' => [
//                'path/to/your/first/bootstrap/file.php',
//                'path/to/your/second/bootstrap/file.php',
            ],

            // Yii::$app config
            'yiiApp' => [
                // config file path or array
                $appBasePath . '/config/web.php',
            ]
        ],

        // configuration for a user module
        // all tests in this directory will use $testPath config merged with
        // $testpath/user config.
//        $testsPath . '/user' => []
    ],

    // if you want to overwrite Yii::$app configuration build using 'byPath'
    // you can set it here
    'yiiAppLast' => [
        // config file path or array
//        'path/to/a/file.php',
    ]
];

// Initialize tests environment generator
pyd\testkit\Test::$environment = new pyd\testkit\Environment($environmentConfig);
