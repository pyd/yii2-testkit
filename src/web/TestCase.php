<?php
namespace pyd\testkit\web;

use pyd\testkit\Tests;

/**
 * Base class for web test case.
 * 
 * The default setting is 'isolation' oriented. A selenium session is created
 * before and destroyed after each test method. Therefor the browser is launched
 * and closed accordingly.
 * 
 * Check the @see $shareWebDriver property to increase the testing speed.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TestCase extends \pyd\testkit\TestCase
{
    /**
     * @var \pyd\testkit\web\Driver
     */
    public $webDriver;
    /**
     * Share the web driver session between the test methods of this test case.
     * 
     * If set to false, a selenium session is created - the browser is launched
     * - and destroyed - the browser is closed - for each test method. This will
     * ensure isolation but will increase the testing time.
     * 
     * If set to true, a selenium session is created - and the browser launched -
     * once at the beginning of the test case. It remains opened untill the end
     * of the test case unless it is willingly destroyed. in this case a new
     * session is created for the next test method.
     * 
     * @var boolean use the same web driver instance for each test method
     */
    public static $shareWebDriver = false;
    /**
     * If set to true, cookies will be deleted after each test method.
     *
     * @var boolean
     */
    public static $clearCookies = true;

    /**
     * Web driver configuration.
     *
     * Basic config for firefox.
     * 
     * @see \RemoteWebDriver::create()
     *
     * @return array
     */
    public static function webDriverConfig()
    {
        $caps = \DesiredCapabilities::firefox();
        return [
            'url' => 'http://localhost:4444/wd/hub',
            'desiredCapabilities' => $caps,
            'connectionTimeout' => null,
            'requestTimeout' => null
        ];
    }
}
