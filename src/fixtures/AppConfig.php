<?php
namespace pyd\testkit\fixtures;

use pyd\testkit\EventsDispatcher;
use yii\base\InvalidConfigException;

/**
 * Provide Yii app config, bootstrap files to load and server variable to initialize.
 *
 * The @see $globalConfig property must be initialized at creation with an
 * array or the path to a file returning an array.
 *
 * ```php
 * // Each key is a path to a directory of the testing tree and it's value is
 * // an array of configuration data.
 * // A Configuration data array can contain 3 kinds of keys:
 * // - the @see BOOTSTRAP_FILES_KEY key which value is an array of bootstrap files
 * //       ['/path/to/bootstrap/file_1.php', '/path/to/bootstrap/file_2.php', ...];
 * // - the @see SERVER_VARS_KEY key which value is an array of server variables to initialize
 * //       ['SERVER_NAME' => 'http://domain.com', 'SCRIPT_NAME' => 'http://domain.com/index-test.php', ...];
 * // - @see APP_KEY key which value is an array of config files and or config arrays
 * //       ['/path/to/yii/app/config/file_1.php', $configArray ], ...];
 *
 * $globalConfig = [
 *
 *      // config for all test cases of the /var/www/myApp/tests directory
 *      '/var/www/myApp/tests' => [
 *
 *          AppConfig::BOOTSTRAP_FILES_KEY => [
 *              '/var/www/myApp/common/config/bootstrap.php'
 *          ],
 *          AppConfig::APP_KEY => [
 *              '/var/www/myApp/common/config/main.php',
 *              '/var/www/myApp/common/config/main-local.php'
 *          ]
 *      ],
 *
 *      // config for all test cases of the /var/www/myApp/tests/frontend directory
 *      '/var/www/myApp/tests/frontend' => [
 *
 *          AppConfig::BOOTSTRAP_FILES_KEY => [
 *              '/var/www/myApp/frontend/config/bootstrap.php'
 *          ],
 *          AppConfig::SERVER_VARS_KEY => [
 *              'SERVER_NAME' => 'http://domain.com',
 *              'SCRIPT_NAME' => 'http://domain.com/index-test.php',
 *              'SCRIPT_FILENAME' => '/var/www/myApp/frontend/web/index-test.php',
 *          ],
 *          AppConfig::APP_KEY => [
 *              '/var/www/myApp/frontend/config/main.php',
 *              '/var/www/myApp/frontend/config/main-local.php'
 *          ]
 *      ],
 *
 *      // config for all test cases of the /var/www/myApp/tests/backend directory
 *      '/var/www/myApp/tests/backend' => [
 *
 *          AppConfig::BOOTSTRAP_FILES_KEY => [
 *              '/var/www/myApp/backend/config/bootstrap.php'
 *          ],
 *          AppConfig::SERVER_VARS_KEY => [
 *              'SERVER_NAME' => 'http://backend.domain.com',
 *              'SCRIPT_NAME' => 'http://backend.domain.com/index-test.php',
 *              'SCRIPT_FILENAME' => '/var/www/myApp/backend/web/index-test.php',
 *          ],
 *          AppConfig::APP_KEY => [
 *              '/var/www/myApp/backend/config/main.php',
 *              '/var/www/myApp/backend/config/main-local.php'
 *          ]
 *      ]
 * ];
 * ```
 *
 * The @see $testCaseDirPath property is initialized by the @see onSetUpBeforeClass
 * method.
 * Note that this class must be registered as an observer of the 'setUpBeforeClass'
 * before the @see \pyd\testkit\fixtures\App class.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AppConfig extends \yii\base\Object
{
    /**
     * Key for bootstrap files array in the @see $globalConfig
     */
    const BOOTSTRAP_FILES_KEY = 'bootstrap-files';
    /**
     * Key for server variables array in the @see $globalConfig
     */
    const SERVER_VARS_KEY = 'server-vars';
    /**
     * Key for Yii app config array in the @see $globalConfig
     */
    const APP_KEY = 'app';
    /**
     * @var array global config i.e. for all test cases
     */
    protected $globalConfig;
    /**
     * @var string path to the parent directory of the currently executed test
     * case
     */
    protected $testCaseDirPath;

    /**
     * Initialization.
     *
     * Verify that the @see $globalConfig was intialized.
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (null === $this->globalConfig) {
            throw new InvalidConfigException(get_class($this) . '::$globalConfig must be initialized.', 10);
        }
    }

    /**
     * Handle setUpBeforeClass event.
     *
     * Initialize @see $testCaseDirPath.
     *
     * @param string $testCaseClassName class name of the currently executed
     * test case
     */
    public function onSetUpBeforeClass($testCaseClassName)
    {
        $rc = new \ReflectionClass($testCaseClassName);
        $this->testCaseDirPath = dirname($rc->getFileName());
        $this->_configByPath = null;
    }


    /**
     * Return bootstrap files to load according to the $path.
     *
     * @return array [
     *      '/path/to/bootstrap/file/one.php',
     *      '/path/to/bootstrap/file/two.php',
     *      ...
     * ]
     */
    public function getBootstrapFiles()
    {
        $configByPath = $this->getConfigByPath();
        return isset($configByPath[self::BOOTSTRAP_FILES_KEY]) ? $configByPath[self::BOOTSTRAP_FILES_KEY] : [];
    }

    /**
     * Return server variables to be initialized according to the $path.
     *
     * @return array [
     *      'serverVarName' => $serverVarNameValue,
     *      'otherServerVarName' => $otherServerVarNameValue,
     *      ...
     * ]
     */
    public function getServerVars()
    {
        $config = $this->getConfigByPath();
        return isset($config[self::SERVER_VARS_KEY]) ? $config[self::SERVER_VARS_KEY] : [];
    }

   /**
    * Return the configuration used to create the Yii app according to the $path.
    *
    * @param string $path
    * @return array
    * @throws \yii\base\InvalidParamException configuration is empty for this
    * path
    */
    public function getAppConfig()
    {
        $config = $this->getConfigByPath();
        if (isset($config[self::APP_KEY]) && !empty($config[self::APP_KEY])) {
            return $config[self::APP_KEY];
        }
        throw new \yii\base\InvalidParamException("yii app configuration is empty for the path '$path'.");
    }

    public function getTestCaseDirPath()
    {
        return $this->testCaseDirPath;
    }

    /**
     * Setter for the @see $globalConfig property.
     *
     * This method will verify the $config argument:
     * - it must be an array or a string;
     * - if a string it must be the path to a file returning an array;
     * - if an array it cannot be empty;
     * - each key must be a path to an existing directory;
     * - bootstrap files:
     *      - must be listed in an array;
     *      - must exist;
     * - server vars:
     *      - must be listed in an array;
     * - app config:
     *      - must be an array or a string;
     *      - if a string it must be the path to a file returning an array;
     *
     *
     * @param string|array $config
     * @see $globalConfig
     */
    protected function setGlobalConfig($config)
    {
        $config = $this->configToArray($config);

        if ([] === $config) {
            throw new InvalidConfigException("Global config cannot be empty.", 30);
        }

        foreach ($config as $path => $data) {

            if (!is_dir($path)) {
                throw new InvalidConfigException("Each key of the configuration"
                        . " array must be a path to a tests directory. The path '$path' is invalid.", 40);
            }

            // check bootstrap files config: must be an array of file paths
            if (isset($data[self::BOOTSTRAP_FILES_KEY])) {

                if (is_array($data[self::BOOTSTRAP_FILES_KEY])) {
                    foreach ($data[self::BOOTSTRAP_FILES_KEY] as $bootstrapFile) {
                        if (!is_file($bootstrapFile)) {
                            throw new InvalidConfigException("Invalid bootstrap file '$bootstrapFile'.", 51);
                        }
                    }
                } else {
                    throw new InvalidConfigException("Bootstrap files must be listed in an array.", 50);
                }

            }

            // check server vars config: must be an array
            if (isset($data[self::SERVER_VARS_KEY])) {

                if (!is_array($data[self::SERVER_VARS_KEY])) {
                    throw new InvalidConfigException("Server vars must be listed in an array.", 60);
                }
            }

            // check Yii app config:
            if (isset($data[self::APP_KEY])) {

                $mergedAppConfig = [];

                foreach ($data[self::APP_KEY] as $appConfig) {
                    $appConfigArray = $this->configToArray($appConfig);
                    $mergedAppConfig = \yii\helpers\ArrayHelper::merge($mergedAppConfig, $appConfigArray);
                }
                $config[$path][self::APP_KEY] = $mergedAppConfig;
            }
        }
        $this->globalConfig = $config;
    }
    private $_configByPath;

    /**
     * Get the config - bootstrap files, server variables and Yii app config -
     * that matches the $path argument.
     *
     * This method will search for all paths in the @see $globalConfig that are
     * parents of the $path argument and merge their configs.
     *
     * @see searchGlobalConfigMatchingPaths
     *
     * @param string $path the path to the directory of the tested case
     *
     * @return array
     */
    protected function getConfigByPath()
    {
        if (null === $this->_configByPath) {
            $globalConfigMatchingPaths = $this->searchGlobalConfigMatchingPaths();
            sort($globalConfigMatchingPaths);
            $configByPath = [];
            foreach ($globalConfigMatchingPaths as $globalConfigPath) {
                $configByPath = \yii\helpers\ArrayHelper::merge($configByPath, $this->globalConfig[$globalConfigPath]);
            }
            $this->_configByPath = $configByPath;
        }
        return $this->_configByPath;
    }

    /**
     * Search in the @see $globalConfig root keys for paths that are equal or
     * ancestors of the @see $testCaseDirPath.
     *
     * If $path is '/var/www/myApp/frontend' the returned array will contain:
     * - '/var/www/myApp/tests';
     * - '/var/www/myApp/tests/frontend'
     *
     * @return array [$path, ...]
     */
    protected function searchGlobalConfigMatchingPaths()
    {
        $matchingPaths = [];
        foreach ($this->globalConfig as $path => $value) {
            if (false !== strpos($this->testCaseDirPath, rtrim($path, '/'))) {
                $matchingPaths[] = $path;
            }
        }
        return $matchingPaths;
    }

    /**
     * Return the $config argument as an array.
     *
     * Verify that the $config argument is an array or a string.
     * If the latter it must be the path to an existing file returning an array.
     *
     * @param string|array $config
     * @return array
     * @throws InvalidConfigException config cannot be resolved as an array
     */
    protected function configToArray($config)
    {
        if (is_string($config) && is_file($config)) $config = include $config;
        if (is_array($config)) return $config;
        throw new InvalidConfigException("Config must be an array or the path to a file returning an array.", 20);
    }

}
