<?php
namespace pyd\testkit\base;

/**
 * Test case base class.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \pyd\testkit\TestManager
     */
    private static $testManager;

    public static function setTestManager(\pyd\testkit\TestManager $testManager)
    {
        self::$testManager = $testManager;
    }

    /**
     * This method is executed at least once when PHPUnit will start processing
     * this test case. it is also executed before each test method that runs in
     * 'isolation'.
     *
     * Don't forget to call parent::setUpBeforeClass() when overriding.
     */
    public static function setUpBeforeClass()
    {
        self::$testManager->onSetUpBeforeClass(get_called_class());
    }

    /**
     * This method is executed before each test method.
     */
    public function setUp()
    {
        self::$testManager->onSetUp();
    }

    /**
     * This method is executed at least once when PHPUnit will end processing
     * this test case. it is also executed after each test method that runs in
     * 'isolation'.
     */
    public static function tearDownAfterClass()
    {
        self::$testManager->onTearDownAfterClass();
    }
}
