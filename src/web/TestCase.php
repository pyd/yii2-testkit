<?php
namespace pyd\testkit\web;

use pyd\testkit\Tests;

/**
 * Base class for web test case.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TestCase extends \pyd\testkit\TestCase
{
    /**
     * @var \pyd\testkit\web\RemoteDriver web driver instance
     */
    public $webDriver;
    
    /**
     * Use the same browser session to perform all tests of this test case - if
     * set to true - vs open and close browser for each test - if set to false.
     * 
     * Note that you can set this property to true and force browser session
     * renewing at the end of a test.
     * ```php
     * public function testOneFeature()
     * {
     *      // some testing that make you want to restart browser session
     *      $this->webDriver->quit();
     * }
     * 
     * @see $shareBrowserCookies 
     * 
     * @var boolean use the same web driver instance for each test method
     */
    public static $shareBrowserSession = false;
    
    /**
     * If set to true, the browser visible cookies will be deleted after each
     * test method. Note that this property is relevant only when
     * {@see $shareBrowserSession} is set to true.
     * 
     * @var boolean
     */
    public static $shareCookies = false;
}
