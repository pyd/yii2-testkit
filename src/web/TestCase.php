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
     * @var string Selenium server listening url
     */
    public $seleniumUrl = 'http://localhost:4444/wd/hub';
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
    protected $shareWebDriver = false;
    /**
     * If set to true, all cookies will be deleted between each test method @see tearDown.
     * Note that this is relevant only if @see $shareWebDriver is enabled.
     *
     * @var boolean delete all cookies between each test method
     */
    protected $clearCookies = true;

    /**
     * Create web driver instance.
     *
     * @return \pyd\testkit\web\Driver
     */
    protected function createWebDriver()
    {
        try {
            $this->webDriver = Driver::create($this->seleniumUrl, $this->getDesiredCapabilities());
        } catch (\WebDriverCurlException $e) {
            throw new \yii\base\InvalidCallException("Cannot create webDriver: " . $e->getMessage());
        }
    }

    /**
     * Selenium session features.
     *
     * This is a basic implementation.
     * @link https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities
     * @see \DesiredCapabilities
     *
     * @return \DesiredCapabilities
     */
    protected function getDesiredCapabilities()
    {
        $caps = new \DesiredCapabilities();
        $caps->setBrowserName(\WebDriverBrowserType::FIREFOX);
        $caps->setPlatform(\WebDriverPlatform::LINUX);
        $caps->setCapability('firefox_binary', '/opt/firefox-40.0.3/firefox-bin');
//        $profile = new \FirefoxProfile();
//         prof.setPreference("xpinstall.signatures.required", false);
//        $profile->setPreference("xpinstall.signatures.required", false);
//        $profile->setPreference("toolkit.telemetry.reportingpolicy.firstRun", false);
//        $profile->setPreference(
//          'browser.startup.homepage',
//          'https://github.com/facebook/php-webdriver/'
//        );
//        $caps->setCapability(\FirefoxDriver::PROFILE, $profile);
        return $caps;
    }

    public function setUp()
    {
        parent::setUp();
        if (null === $this->webDriver) {
            $this->createWebDriver();
        }
    }

    public function tearDown()
    {
        if (!$this->shareWebDriver) {
            $this->webDriver->quit();
            $this->webDriver = null;
        } else if ($this->clearCookies) {
            $this->webDriver->cookies()->deleteAll();
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass()
    {
        self::$webDriver->quit();
        parent::tearDownAfterClass();
    }
}
