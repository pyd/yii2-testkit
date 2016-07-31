<?php
namespace tests\unit;

use pyd\testkit\fixtures\AppConfig;
use yii\base\InvalidConfigException;

/**
 * Unit testing of the \pyd\testkit\fixtures\AppConfig class.
 *
 * Verifications:
 * - $globalConfig property must be initialized at creation;
 * - $globalConfig property must be valid
 * - getBootstrapFiles method returns expected result;
 * - getServerVars method returns expected result;
 * - getAppConfig method returns expected result;
 *
 * Note that the AppConfig class verify the paths to the directories and files.
 * A fake file system was created to perform this tests.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AppConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Return a fake global config for this test case.
     *
     * @return array
     */
    public static function globalConfig()
    {
        $bootstrapFilesKey = AppConfig::BOOTSTRAP_FILES_KEY;
        $serverVarsKey = AppConfig::SERVER_VARS_KEY;
        $appConfigKey = AppConfig::APP_KEY;
        $fakeFileSystemBasePath = \Yii::getAlias('fs');

        return [

            // config for the test cases in the /tests folder i.e. all test cases
            $fakeFileSystemBasePath . '/var/www/html/domain.com/tests' => [

                $bootstrapFilesKey => [
                    $fakeFileSystemBasePath . '/var/www/html/domain.com/common/config/bootstrap.php'
                ],

                $appConfigKey => [
                    [
                        'language' => 'en',
                        'params' => [
                            'adminMail' => 'admin@domain.com'
                        ]
                    ]
                ]
            ],

            // config for the test cases in the /tests/frontend folder
            $fakeFileSystemBasePath . '/var/www/html/domain.com/tests/frontend' => [

                $bootstrapFilesKey => [
                    $fakeFileSystemBasePath . '/var/www/html/domain.com/frontend/config/bootstrap.php'
                ],

                $serverVarsKey => [
                    'SERVER_NAME' => 'http://domain.com',
                    'SCRIPT_NAME' => 'http://domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fakeFileSystemBasePath . '/var/www/html/domain.com/frontend/web/index-test.php',
                ],

                $appConfigKey => [
                    [
                        'id' => 'frontend app',
                        'name' => 'My over tested app',
                        'components' => [
                            'db' => []
                        ],
                    ]
                ]
            ],

            // config for the test cases in the /tests/backend folder
            $fakeFileSystemBasePath . '/var/www/html/domain.com/tests/backend' => [

                $bootstrapFilesKey => [
                    $fakeFileSystemBasePath . '/var/www/html/domain.com/backend/config/bootstrap.php'
                ],

                $serverVarsKey => [
                    'SERVER_NAME' => 'http://backend.domain.com',
                    'SCRIPT_NAME' => 'http://backend.domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fakeFileSystemBasePath . '/var/www/html/domain.com/backend/web/index-test.php',
                ],

                $appConfigKey => [
                    [
                        'id' => 'backend app',
                        'name' => 'My backend app'
                    ]
                ]
            ],

            // config for the test cases in the /tests/backend/admin folder
            $fakeFileSystemBasePath . '/var/www/html/domain.com/tests/backend/admin' => [
                $serverVarsKey => [
                    'SERVER_NAME' => 'http://admin.domain.com',
                    'SCRIPT_NAME' => 'http://admin.domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fakeFileSystemBasePath . '/var/www/html/domain.com/backend/web/index-admin-test.php',
                ],

                $appConfigKey => [
                    [
                        'name' => 'My private zone in backend app',
                        'language' => 'fr',
                    ]
                ]
            ]
        ];
    }

    /**
     * Verify that the global configuration property must be initialized at creation.
     *
     * Creating an instance without providing the $globalConfig must thrown an
     * invalidConfigException.
     */
    public function testGlobalConfigPropertyMustBeInitializedAtCreation()
    {
        try {
            new AppConfig();
        } catch (InvalidConfigException $e) {
            $this->assertEquals(10, $e->getCode(), "Wrong exception code. Exception catched is not the good one.");
            return;
        }

        $this->fail("A \yii\base\InvalidConfigException should be thrown when config is not initialized.");
    }

    /**
     * Provide invalid global configuration.
     *
     * @return array
     */
    public function dataProviderInvalidGlobalConfig()
    {
        $fakeFileSystemBasePath = \Yii::getAlias('fs');
        $bootstrapFilesKey = AppConfig::BOOTSTRAP_FILES_KEY;
        $serverVarsKey = AppConfig::SERVER_VARS_KEY;
        $appConfigKey = AppConfig::APP_KEY;

        return [
            // config must be an array or a file
            [new \stdClass(), 20],
            [false, 20],
            // config as a file must exist
            [$fakeFileSystemBasePath . '/var/www/html/domain.com/frontend/config/non_existing_file.php', 20],
            // config as a file must return an array
            [$fakeFileSystemBasePath . '/var/www/html/domain.com/frontend/config/configFileReturningNothing.php', 20],
            // config as an array cannot be empty
            [array(), 30],
            // config keys must be a path to an existing directory
            [['/path/to/a/non/existing/directory' => []], 40],
            // bootstrap files must be listed in an array
            [[$fakeFileSystemBasePath . '/var/www/html/domain.com/frontend' => [$bootstrapFilesKey => 'should_be_an_array']], 50],
            // bootstrap file must be valid
            [[$fakeFileSystemBasePath . '/var/www/html/domain.com/frontend' => [$bootstrapFilesKey => ['/path/to/non/existing/bootstrap_file.php']]], 51],
            // server vars must be listed in an array
            [[$fakeFileSystemBasePath . '/var/www/html/domain.com/frontend' => [$serverVarsKey => 'should_be_an_array']], 60],
            // app config must be a string or an array
            [[$fakeFileSystemBasePath . '/var/www/html/domain.com/frontend' => [$appConfigKey => [false]]], 20],
            // app config, if a string, must return an array
            [[$fakeFileSystemBasePath . '/var/www/html/domain.com/frontend' => [
                    $appConfigKey => [$fakeFileSystemBasePath . '/var/www/html/domain.com/frontend/config/configFileReturningNothing.php']
                ]
            ], 20]
        ];
    }

    /**
     * Verify that the global configuration must be valid.
     *
     * Creating an instance with an invalid global config must throw an
     * invalidConfigException.
     *
     * @see dataProviderInvalidglobalConfig()
     * @see \pyd\testkit\AppConfig::setGlobalConfig()
     *
     * @dataProvider dataProviderInvalidglobalConfig
     *
     * @param mixed $globalConfig
     */
    public function testGlobalConfigMustBeValid($globalConfig, $expectedExceptionCode)
    {
        try {
            new AppConfig(['globalConfig' => $globalConfig]);
        } catch (InvalidConfigException $e) {
            $this->assertEquals($expectedExceptionCode, $e->getCode(),
                    "Exception code is not the expected one." . $e->getMessage());
            return;
        }
        $this->fail("An InvalidConfigException should be thrown when global config is invalid.");
    }

    /**
     * Data provider for @see testMethodGetBootstrapFiles().
     *
     * Each item contains:
     * - a test case class name;
     * - an array of the bootstrap files that the @see AppConfig::getBootstrapFiles()
     * should return for this test case;
     *
     * @return array
     */
    public function expectedBootstrapFilesByPath()
    {
        $fakeFileSystemBasePath = \Yii::getAlias('fs');

        return [
            [
                // test case class name
                '\tests\RootTest',
                // bootstrap file that should be loaded for the test case
                [
                    $fakeFileSystemBasePath . '/var/www/html/domain.com/common/config/bootstrap.php'
                ]
            ],
            [
                '\tests\frontend\user\auth\ConnectionTest',
                [
                    $fakeFileSystemBasePath . '/var/www/html/domain.com/common/config/bootstrap.php',
                    $fakeFileSystemBasePath . '/var/www/html/domain.com/frontend/config/bootstrap.php'
                ]
            ]
        ];
    }

    /**
     * Verify that @see AppConfig::getBootstrapFiles() returns the expected
     * bootstrap files according to the $testCaseClassName  argument.
     *
     * @dataProvider expectedBootstrapFilesByPath
     *
     * @param string $testCaseClassName class name of a test case
     * @param array $expectedBootstrapFiles
     */
    public function testMethodGetBootstrapFiles($testCaseClassName, array $expectedBootstrapFiles)
    {
        $appConfig = new AppConfig(['globalConfig' => self::globalConfig()]);
        $appConfig->onSetUpBeforeClass($testCaseClassName);

        $actualBootstrapFiles = $appConfig->getBootstrapFiles();
        $this->assertEquals($actualBootstrapFiles, $expectedBootstrapFiles);
    }

    /**
     * Data provider for @see testMethodGetServerVars().
     *
     * Each item contains:
     * - a test case class name;
     * - an array of the server variables that the @see AppConfig::getServerVars()
     * should return for this test case;
     *
     * @return array
     */
    public function expectedServerVarsByPath()
    {
        $fakeFileSystemBasePath = \Yii::getAlias('fs');

        return [
            [
                // the test case class name
                '\tests\RootTest',
                // server variables that should be initialized for this test case
                []
            ],
            [
                '\tests\frontend\user\auth\ConnectionTest',
                [
                    'SERVER_NAME' => 'http://domain.com',
                    'SCRIPT_NAME' => 'http://domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fakeFileSystemBasePath . '/var/www/html/domain.com/frontend/web/index-test.php',
                ]
            ],
            [
                '\tests\backend\admin\AdminHomeTest',
                [
                    'SERVER_NAME' => 'http://admin.domain.com',
                    'SCRIPT_NAME' => 'http://admin.domain.com/index-test.php',
                    'SCRIPT_FILENAME' => $fakeFileSystemBasePath . '/var/www/html/domain.com/backend/web/index-admin-test.php',
                ]
            ]
        ];
    }

    /**
     * Verify that @see AppConfig::getServerVars() returns the expected
     * server variables according to the $testCaseClassName  argument.
     *
     * @dataProvider expectedServerVarsByPath
     *
     * @param string $testCaseClassName class name of a test case
     * @param array $expectedServerVars
     */
    public function testMethodGetServerVars($testCaseClassName, array $expectedServerVars)
    {
        $appConfig = new AppConfig(['globalConfig' => self::globalConfig()]);
        $appConfig->onSetUpBeforeClass($testCaseClassName);
        $actualServerVars = $appConfig->getServerVars();
        $this->assertEquals($expectedServerVars, $actualServerVars);
    }

    /**
     * Data provider for @see testMethodGetAppConfig().
     *
     * Each item contains:
     * - a test case class name;
     * - the configuration that the @see AppConfig::getAppConfig() should return
     * for this test case;
     *
     * @return array
     */
    public function expectedYiiAppConfigByPath()
    {
        return [
            [
                '\tests\RootTest',
                [
                    'language' => 'en',
                    'params' => ['adminMail' => 'admin@domain.com']
                ]
            ],
            [
                '\tests\frontend\user\auth\ConnectionTest',
                [
                    'language' => 'en',
                    'id' => 'frontend app',
                    'name' => 'My over tested app',
                    'components' => ['db' => []],
                    'params' => ['adminMail' => 'admin@domain.com']
                ]
            ],
            [
                '\tests\backend\admin\AdminHomeTest',
                [
                    'language' => 'fr',
                    'id' => 'backend app',
                    'name' => 'My private zone in backend app',
                    'params' => ['adminMail' => 'admin@domain.com']
                ]
            ]
        ];
    }

    /**
     * Verify that @see AppConfig::getAppConfig() returns the expected config
     * according to the $testCaseClassName argument.
     *
     * @dataProvider expectedYiiAppConfigByPath
     * @param string $testCaseClassName class name of a test case
     * @param array $expectedYiiAppConfig
     */
    public function testMethodGetAppConfig($testCaseClassName, array $expectedYiiAppConfig)
    {
        $appConfig = new AppConfig(['globalConfig' => self::globalConfig()]);
        $appConfig->onSetUpBeforeClass($testCaseClassName);
        $actualYiiAppConfig = $appConfig->getAppConfig();
        $this->assertEquals($expectedYiiAppConfig, $actualYiiAppConfig);
    }
}
