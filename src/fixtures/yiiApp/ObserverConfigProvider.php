<?php
namespace pyd\testkit\fixtures\yiiApp;

use yii\base\InvalidConfigException;

/**
 * Provide a config to create a Yii application based on the path to a tests
 * directory.
 * 
 * This 'by-path-config' can contain the Yii app config and, in addition, some
 * $_SERVER variables to set and some bootstrap files to load, before creating
 * the app.
 * 
 * The @see $globalConfig property must be initialized at instantiation.
 * <code>
 * $appConfig = new AppConfig(['globalConfig' => $globalConfig]);
 * </code>
 *
 * Example of a $globalConfig content:
 * <code>
 * $globalConfig = [
 *
 *      // config for all test cases of the /var/www/myApp/tests directory
 *      '/var/www/myApp/tests' => [
 *
 *          ObserverConfigProvider::BOOTSTRAP_FILES_KEY => [
 *              '/var/www/myApp/common/config/bootstrap.php'
 *          ],
 *          ObserverConfigProvider::APP_KEY => [
 *              '/var/www/myApp/common/config/main.php',
 *              '/var/www/myApp/common/config/main-local.php'
 *          ]
 *      ],
 *
 *      // config for all test cases of the /var/www/myApp/tests/frontend directory
 *      '/var/www/myApp/tests/frontend' => [
 *
 *          ObserverConfigProvider::BOOTSTRAP_FILES_KEY => [
 *              '/var/www/myApp/frontend/config/bootstrap.php'
 *          ],
 *          ObserverConfigProvider::SERVER_VARS_KEY => [
 *              'SERVER_NAME' => 'http://domain.com',
 *              'SCRIPT_NAME' => 'http://domain.com/index-test.php',
 *              'SCRIPT_FILENAME' => '/var/www/myApp/frontend/web/index-test.php',
 *          ],
 *          ObserverConfigProvider::APP_KEY => [
 *              '/var/www/myApp/frontend/config/main.php',
 *              '/var/www/myApp/frontend/config/main-local.php'
 *          ]
 *      ],
 *
 *      // config for all test cases of the /var/www/myApp/tests/backend directory
 *      '/var/www/myApp/tests/backend' => [
 *
 *          ObserverConfigProvider::BOOTSTRAP_FILES_KEY => [
 *              '/var/www/myApp/backend/config/bootstrap.php'
 *          ],
 *          ObserverConfigProvider::SERVER_VARS_KEY => [
 *              'SERVER_NAME' => 'http://backend.domain.com',
 *              'SCRIPT_NAME' => 'http://backend.domain.com/index-test.php',
 *              'SCRIPT_FILENAME' => '/var/www/myApp/backend/web/index-test.php',
 *          ],
 *          ObserverConfigProvider::APP_KEY => [
 *              '/var/www/myApp/backend/config/main.php',
 *              '/var/www/myApp/backend/config/main-local.php'
 *          ]
 *      ]
 * ];
 * ```
 *
 * The @see generateConfigByPath() will generate the configuration for a specific
 * test case. It is called by the @see onSetUpBeforeClass() method. This class
 * must therefor be registered as an observer of the 'setUpBeforeClass' event.
 *
 * Once generated you can get configuration data to create a Yii app.
 * ```php
 * $appConfig->getBootstrapFiles();
 * $appConfig->getServerVars();
 * $appConfig->getAppConfig();
 * ```
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ObserverConfigProvider extends \yii\base\Object
{
    /**
     * Key of the items that contain  bootstrap files in $globalConfig
     */
    const BOOTSTRAP_FILES_KEY = 'bootstrap-files';
    /**
     * Key of the items that contain $_SERVER variables in $globalConfig
     */
    const SERVER_VARS_KEY = 'server-vars';
    /**
     * Key of the items that contain Yii app config in $globalConfig
     */
    const APP_KEY = 'app';
    /**
     * @var array global config
     */
    protected $globalConfig;
    /**
     * @var array specific config generated according to $testCaseDirPath
     */
    protected $configByPath;
    /**
     * @var string path to the directory of the currently executed test case
     */
    protected $testCaseDirPath;

    /**
     * The $globalConfig property must be intialized.
     */
    public function init()
    {
        if (null === $this->globalConfig) {
            throw new InvalidConfigException(get_class($this) . '::$globalConfig should be initialized.', 10);
        }
    }

    /**
     * Get bootstrap files.
     *
     * This method can return an empty array.
     * 
     * @return array [
     *      '/path/to/bootstrap/file/one.php',
     *      '/path/to/bootstrap/file/two.php',
     *      ...
     * ]
     */
    public function getBootstrapFiles()
    {
        return isset($this->configByPath[self::BOOTSTRAP_FILES_KEY]) ? $this->configByPath[self::BOOTSTRAP_FILES_KEY] : [];
    }

    /**
     * Get $_SERVER variables.
     *
     * This method can return an empty array.
     * 
     * @return array [
     *      'serverVarName' => $serverVarNameValue,
     *      'otherServerVarName' => $otherServerVarNameValue,
     *      ...
     * ]
     */
    public function getServerVars()
    {
        return isset($this->configByPath[self::SERVER_VARS_KEY]) ? $this->configByPath[self::SERVER_VARS_KEY] : [];
    }

   /**
    * Get config to create Yii app.
    * 
    * @return array
    * @throws \LogicException config is empty
    */
    public function getAppConfig()
    {
        $config = isset($this->configByPath[self::APP_KEY]) ? $this->configByPath[self::APP_KEY] : [];
        if (!empty($config)) {
            return $config;
        } else {
            throw new \LogicException("Config for Yii app should not be empty.");
        }
    }

    /**
     * Handle the 'setUpBeforeClass' event.
     *
     * @param string $testCaseClassName class of the currently executed test
     * case
     */
    public function onSetUpBeforeClass($testCaseClassName)
    {        
        $rc = new \ReflectionClass($testCaseClassName);
        $this->testCaseDirPath = dirname($rc->getFileName());
        $this->generateConfigByPath();
    }

    /**
     * Generate a specific configuration for the currently executed test case.
     *
     * @see $testCaseDirPath
     */
    protected function generateConfigByPath()
    {
        $globalConfigMatchingPaths = $this->searchGlobalConfigMatchingPaths();
        sort($globalConfigMatchingPaths);
        $configByPath = [];
        foreach ($globalConfigMatchingPaths as $globalConfigPath) {
            $configByPath = \yii\helpers\ArrayHelper::merge($configByPath, $this->globalConfig[$globalConfigPath]);
        }
        $this->configByPath = $configByPath;
    }

    /**
     * Set global config.
     * 
     * This method will resolve file paths to arrays and verify the config
     * content.
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

            // check Yii app config
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

    /**
     * Search in the @see $globalConfig root keys for paths that are equal or
     * ancestors of the @see $testCaseDirPath.
     *
     * If $path to the directory of the test case is '/var/www/myApp/frontend',
     * then the returned array will contain:
     * - '/var/www/myApp/tests';
     * - '/var/www/myApp/tests/frontend'
     *
     * @return array [$path, ...]
     */
    protected function searchGlobalConfigMatchingPaths()
    {
        if (null === $this->testCaseDirPath) {
            throw new \yii\base\InvalidCallException("Property \$testCaseDirPath should have been initialized.");
        }
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
        throw new InvalidConfigException("Config must be an array or the path to a file returning an array. $config", 20);
    }
}
