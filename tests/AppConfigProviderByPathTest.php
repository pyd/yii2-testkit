<?php
namespace pyd\testkit\tests;

use \pyd\testkit\AppConfigProviderByPath;
use yii\base\InvalidConfigException;

/**
 * Tests of the \pyd\testkit\AppConfigProviderByPath class.
 *
 * Verifications:
 * - global config is required by constructor;
 * - global config must be valid;
 * - $testCaseDirPath argument passed to API methods must be valid;
 * - method @see \pyd\testkit\AppConfigProviderByPath::getBootstrapFiles must
 *   return expected result;
 * - method @see \pyd\testkit\AppConfigProviderByPath::getServerVars must
 *   return expected result;
 * - method @see \pyd\testkit\AppConfigProviderByPath::getAppConfig must
 *   return expected result;
 *
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AppConfigProviderByPathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Configuration fixture provided to the AppConfigProviderByPath instance.
     *
     * @return array
     */
    protected static function allTestsConfig()
    {
        $bootstrapFilesKey = AppConfigProviderByPath::BOOTSTRAP_FILES_KEY;
        $serverVarsKey = AppConfigProviderByPath::SERVER_VARS_KEY;
        $appConfigKey = AppConfigProviderByPath::APP_KEY;
        $fsBasePath = self::getFakeFsBasePath();

        return [

            $fsBasePath . '/var/www/html/domain.com/tests' => [

                $bootstrapFilesKey => [
                    $fsBasePath . '/var/www/html/domain.com/common/config/bootstrap.php'
                ],

                $appConfigKey => [
                    'id' => 'tests'
                ]
            ],

            $fsBasePath . '/var/www/html/domain.com/tests/common' => [],

            $fsBasePath . '/var/www/html/domain.com/tests/frontend' => [

                $bootstrapFilesKey => [
                    $fsBasePath . '/var/www/html/domain.com/frontend/config/bootstrap.php'
                ],

                $serverVarsKey => [
                    'SERVER_NAME' => 'http://domain.com',
                    'SCRIPT_NAME' => 'http://domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fsBasePath . '/var/www/html/domain.com/frontend/web/index-test.php',
                ],

                $appConfigKey => [
                    'id' => 'frontend'
                ]
            ],

            $fsBasePath . '/var/www/html/domain.com/tests/backend' => [

                $bootstrapFilesKey => [
                    $fsBasePath . '/var/www/html/domain.com/backend/config/bootstrap.php'
                ],

                $serverVarsKey => [
                    'SERVER_NAME' => 'http://domain.com',
                    'SCRIPT_NAME' => 'http://backend.domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fsBasePath . '/var/www/html/domain.com/backend/web/index-test.php',
                ],

                $appConfigKey => [
                    'id' => 'backend',
                ]
            ]
        ];
    }

    /**
     * Return path to the root of the fake file system.
     *
     * @return string
     */
    protected static function getFakeFsBasePath()
    {
        return \Yii::getAlias('@tests') . '/fs';
    }

    /**
     * A Global config must be passed to the constructor.
     *
     * Verify that an exception is thrown if the
     * [[\pyd\testkit\AppConfigProviderByPath::allTestsConfig]] property is not
     * initialized.
     *
     * InvalidConfigException with code 10 is thrown by the
     * [[\pyd\testkit\AppConfigProviderByPath::init]] method.
     */
    public function testGlobalConfigMustBeInitializedAtCreation()
    {

        try {
            $instance = new AppConfigProviderByPath();
        } catch (InvalidConfigException $e) {
            $this->assertEquals(10, $e->getCode());
            return;
        }
        $this->fail("An InvalidConfigException should be thrown when global"
                . " config is not initialized.");
    }

    /**
     * Invalid configurations for the tested class.
     *
     * @return array
     */
    public function dataProviderInvalidAllTestsConfig()
    {
        $fsBasePath = self::getFakeFsBasePath();
        $bootstrapFilesKey = AppConfigProviderByPath::BOOTSTRAP_FILES_KEY;
        $serverVarsKey = AppConfigProviderByPath::SERVER_VARS_KEY;
        $appConfigKey = AppConfigProviderByPath::APP_KEY;

        return [
            // config must be an array or a file
            [new \stdClass()],
            [false],
            // config as a file must exist
            [$fsBasePath . '/var/www/html/domain.com/frontend/config/non_existing_file.php'],
            // config as a file must return an array
            [$fsBasePath . '/var/www/html/domain.com/frontend/config/configFileReturningNothing.php'],
            // config as an array cannot be empty
            [[]],
            // config keys must be a path to an existing directory
            [[
                '/path/to/a/non/existing/directory' => []
            ]],
            // bootstrap files must be listed in an array
            [[
                $fsBasePath . '/var/www/html/domain.com/frontend' => [
                    $bootstrapFilesKey => 'should_be_an_array'
                ]
            ]],
            // bootstrap file must be valid
            [[
                $fsBasePath . '/var/www/html/domain.com/frontend' => [
                    $bootstrapFilesKey => [
                        '/path/to/non/existing/bootstrap_file.php'
                    ]
                ]
            ]],
            // server vars must be listed in an array
            [[
                $fsBasePath . '/var/www/html/domain.com/frontend' => [
                    $serverVarsKey => 'should_be_an_array'
                ]

            ]],
            // app config must be a string or an array
            [[
                $fsBasePath . '/var/www/html/domain.com/frontend' => [
                    $appConfigKey => new \stdClass()
                ]
            ]],
            // app config, if a string, must return an array
            [[
                $fsBasePath . '/var/www/html/domain.com/frontend' => [
                    $appConfigKey => $fsBasePath . '/var/www/html/domain.com/frontend/config/configFileReturningNothing.php'
                ]
            ]]
        ];
    }

    /**
     * Config passed to the tested class must be valid.
     *
     * Verify that a yii\base\InvalidConfigException is thrown when the global
     * config passed to the tested class is invalid.
     * @see dataProviderInvalidAllTestsConfig()
     * @see \pyd\testkit\AppConfigProviderByPath::setAllTestsConfig()
     *
     * @dataProvider dataProviderInvalidAllTestsConfig
     * @param mixed $globalConfig
     */
    public function testGlobalConfigMustBeValid($globalConfig)
    {
        try {
            $instance = new AppConfigProviderByPath(['allTestsConfig' => $globalConfig]);
        } catch (InvalidConfigException $e) {
            return;
        }
        $this->fail("An InvalidConfigException should be thrown when global"
                . " config is invalid.");
    }


    /**
     * Return the names of the methods that require a $testCaseDirPath argument.
     *
     * @return array
     */
    public function dataProviderMethodsRequiringValidTestCaseDirPathArgument()
    {
        return [['getBootstrapFiles', 'getServerVars', 'getAppConfig']];
    }

    /**
     * When a method requires a $testCaseDirPath argument, the provided path
     * must be valid.
     *
     * Verification: call such a method with an invalid $testCaseDirPath argument
     * and verify that an InvalidParamException is thrown.
     *
     * @dataProvider dataProviderMethodsRequiringValidTestCaseDirPathArgument
     * @param string $methodName name of the method to be called with an invalid
     * argument
     */
    public function testTestCaseDirPathArgumentMustBeValid($methodName)
    {
        $instance = new AppConfigProviderByPath(['allTestsConfig' => self::allTestsConfig()]);
        try {
            $instance->$methodName('/invalid/testcase/dir/path');
        } catch (\yii\base\InvalidParamException $e) {
            return;
        }
        $this->fail("Calling method '$methodName' with an invalid \$testCaseDirPath argument"
                . " should throw an InvalidParamException.");
    }

    /**
     * Data provider for @see testMethodGetBootstrapFilesReturnsExpectedResult().
     *
     * @return array
     */
    public function dataProviderBootstrapFilesResult()
    {
        $fsBasePath = self::getFakeFsBasePath();
        return [
            // value of the $testCaseDirPath argument passed to the method
            [$fsBasePath . '/var/www/html/domain.com/tests',
                // list of bootstrap files that the method should return
                [$fsBasePath . '/var/www/html/domain.com/common/config/bootstrap.php']
            ],
            [$fsBasePath . '/var/www/html/domain.com/tests/frontend',
                [$fsBasePath . '/var/www/html/domain.com/common/config/bootstrap.php',
                    $fsBasePath . '/var/www/html/domain.com/frontend/config/bootstrap.php']
            ],
            [$fsBasePath . '/var/www/html/domain.com/tests/backend',
                [$fsBasePath . '/var/www/html/domain.com/common/config/bootstrap.php',
                    $fsBasePath . '/var/www/html/domain.com/backend/config/bootstrap.php']
            ],
            // no bootstrap file to load for this path
            [$fsBasePath . '/var/www/html/domain.com/', []],
        ];
    }

    /**
     * Verify that the @see \pyd\testkit\AppConfigProviderByPath::getBootstrapFiles()
     * returns the expected result.
     *
     * @dataProvider dataProviderBootstrapFilesResult
     * @param string $testCaseDirPath
     * @param array $expectedBootstrapFiles
     */
    public function testMethodGetBootstrapFilesReturnsExpectedResult($testCaseDirPath, array $expectedBootstrapFiles)
    {
        $instance = new AppConfigProviderByPath(['allTestsConfig' => self::allTestsConfig()]);
        $returnedBootstrapFiles = $instance->getBootstrapFiles($testCaseDirPath);
        $this->assertEquals($expectedBootstrapFiles, $returnedBootstrapFiles);
    }

    /**
     * Data provider for @see testMethodGetServerVarsReturnsExpectedResult().
     *
     * @return array
     */
    public function dataProviderServerVarsResult()
    {
        $fsBasePath = self::getFakeFsBasePath();
        return [
            // value of the $testCaseDirPath argument passed to the method
            [$fsBasePath . '/var/www/html/domain.com/tests',
                // list of server variables that the method should return
                []
            ],
            [$fsBasePath . '/var/www/html/domain.com/tests/frontend',
                [
                    'SERVER_NAME' => 'http://domain.com',
                    'SCRIPT_NAME' => 'http://domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fsBasePath . '/var/www/html/domain.com/frontend/web/index-test.php',
                ]
            ],
            [$fsBasePath . '/var/www/html/domain.com/tests/backend',
                [
                    'SERVER_NAME' => 'http://domain.com',
                    'SCRIPT_NAME' => 'http://backend.domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fsBasePath . '/var/www/html/domain.com/backend/web/index-test.php',
                ]
            ]
        ];
    }

    /**
     * Verify that the @see \pyd\testkit\AppConfigProviderByPath::getServerVars()
     * returns the expected result.
     *
     * @dataProvider dataProviderServerVarsResult
     * @param string $testCaseDirPath
     * @param array $expectedServerVars
     */
    public function testMethodGetServerVarsReturnsExpectedResult($testCaseDirPath, array $expectedServerVars)
    {
        $instance = new AppConfigProviderByPath(['allTestsConfig' => self::allTestsConfig()]);
        $returnedServerVars = $instance->getServerVars($testCaseDirPath);
        $this->assertEquals($expectedServerVars, $returnedServerVars);
    }

    /**
     * Data provider for @see testMethodGetAppConfigReturnsExpectedResult().
     *
     * @return array
     */
    public function dataProviderAppConfigResult()
    {
        $fsBasePath = self::getFakeFsBasePath();
        return [
            // value of the $testCaseDirPath argument passed to the method
            [$fsBasePath . '/var/www/html/domain.com/tests',
                // expected value of the app config
                ['id' => 'tests']
            ],
            [$fsBasePath . '/var/www/html/domain.com/tests/frontend',
                ['id' => 'frontend']
            ],
            [$fsBasePath . '/var/www/html/domain.com/tests/backend',
                ['id' => 'backend']
            ],
            [$fsBasePath . '/var/www/html/domain.com/',
                []
            ],
        ];
    }

    /**
     * Verify that the @see \pyd\testkit\AppConfigProviderByPath::getAppConfig()
     * returns the expected result.
     *
     * @dataProvider dataProviderAppConfigResult
     * @param type $testCaseDirPath
     * @param array $expectedAppConfig
     */
    public function testMethodGetAppConfigReturnsExpectedResult($testCaseDirPath, array $expectedAppConfig)
    {
        $instance = new AppConfigProviderByPath(['allTestsConfig' => self::allTestsConfig()]);
        $returnedAppConfig = $instance->getAppConfig($testCaseDirPath);
        $this->assertEquals($expectedAppConfig, $returnedAppConfig);
    }
}
