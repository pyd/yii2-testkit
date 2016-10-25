<?php
namespace pyd\testkit\web;

/**
 * Web Test Case base class.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class TestCase extends \pyd\testkit\base\TestCase
{
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;
    /**
     * Each test method will use the same web driver instance - your browser won't
     * close between each test method. This can speed up a little your tests.
     *
     * Note that cookies will remain in your browser unless you set @see $clearCookies
     * to true.
     *
     * @var boolean use the same web driver instance for each test method
     */
    public static $shareWebDriver = false;
    /**
     * If set to true, all cookies will be deleted between each test method @see tearDown.
     * Note that this is relevant only if @see $shareWebDriver is enabled.
     *
     * @var boolean delete all cookies between each test method
     */
    public static $clearCookies = true;

    /**
     * Define config for the web driver creation.
     *
     * This is a basic config. Adapt it to your needs in your base web test case
     * class.
     *
     * @see \RemoteWebDriver::create()
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

    public static function setUpBeforeClass()
    {
        self::getTestkit()->getWebDriverManager()->registerAsObserver(self::getTestkit()->getEvents());
        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        parent::setUp();
        $this->webDriver = self::getTestkit()->getWebDriverManager()->getDriver();
    }
}
