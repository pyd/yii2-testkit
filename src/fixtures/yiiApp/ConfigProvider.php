<?php
namespace pyd\testkit\fixtures\yiiApp;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

/**
 * Provides config - based on a directory in the tests tree - to create a Yii
 * application.
 * Note that this clas can also provides $_SERVER variables to initialize and
 * bootstrap files to load before creating the Yii app instance.
 * 
 * First, you need to initialize the {@see $_globalConfig} property with an
 * array - or a file returning an array. Example:
 * ```php
 * // each item is 'path/to/a/directory/in/tests/tree => $configForThisDirectory
 * $globalConfig = [
 *
 *      // config for all test cases of the /var/www/myApp/tests directory
 *      '/var/www/myApp/tests' => [
 *
 *          ConfigProvider::BOOTSTRAP_FILES_KEY => [
 *              '/var/www/myApp/config/bootstrap-main.php'
 *          ],
 *          ConfigProvider::APP_KEY => [
 *              '/var/www/myApp/config/main.php',
 *          ]
 *      ],
 *
 *      // config for all test cases of the /var/www/myApp/tests/unctional directory
 *      '/var/www/myApp/tests/functional' => [
 *
 *          ConfigProvider::BOOTSTRAP_FILES_KEY => [
 *              '/var/www/myApp/config/bootstrap-web.php'
 *          ],
 *          ConfigProvider::SERVER_VARS_KEY => [
 *              'SERVER_NAME' => 'http://domain.com',
 *              'SCRIPT_NAME' => 'http://domain.com/index-test.php',
 *              'SCRIPT_FILENAME' => '/var/www/myApp/web/index-test.php',
 *          ],
 *          ConfigProvider::APP_KEY => [
 *              '/var/www/myApp/config/web.php'
 *          ]
 *      ]
 * ];
 * ```
 * 
 * Then after setting the the {@see $testDirectory} property, with the path to 
 * the currently executed test case, you will be able to get a config to create
 * a Yii app for this test case.
 * 
 * ```php
 * $configProvider = new ConfigProvider(['globalConfig' => $globalConfig]);
 * $configProvider->setTestDirectory('/var/www/myApp/tests/functional/users');
 * // return ['/.../bootstrap-main.php', '/.../bootstrap-web.php']
 * $bootstrapFiles = $configProvider->getBootstrapFiles();
 * // return server variables defined for the '/var/www/myApp/tests/functional' directory
 * $serverVars = $configProvider->getServerVars();
 * // return an array of the config/main file merged with the config/web file
 * $yiiAppConfig = $configProvider->getYiiAppConfig();
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ConfigProvider extends \yii\base\Object implements \pyd\testkit\interfaces\InterfaceYiiAppConfigProvider
{
    /**
     * Key of an item containing 'bootstrap files' in the global config.
     */
    const BOOTSTRAP_FILES_KEY = 'bootstrap-files';
    /**
     * Key of an item containing $_SERVER variables in the global config.
     */
    const SERVER_VARS_KEY = 'server-vars';
    /**
     * Key of an item containing a Yii app config in the global config.
     */
    const APP_KEY = 'app';
    /**
     * The path to a directory of the tests tree where the test case is located.
     * @see setTestDirectory()
     * @var string 
     */
    protected $testDirectory;
    /**
     * The config to create a Yii app based on the {@see $testDirectory}
     * @see getTestDirectoryConfig()
     * @var array
     */
    private $_testDirectoryConfig;
    /**
     * Configs for bootstrap files, $_SERVER variables and Yii app indexed by
     * tests tree directories;
     * @var array 
     */
    private $_globalConfig;
    
    /**
     * Get a list of bootstrap files to load before the Yii app creation
     * according to the {@see $testDirectory}.
     * 
     * @return array an empty array if no bootstrap files were defined in
     * {@see $_globalConfig} for the {@see testDirectory}
     */
    public function getBootstrapFiles()
    {
        return isset($this->getTestDirectoryConfig()[self::BOOTSTRAP_FILES_KEY]) ? $this->getTestDirectoryConfig()[self::BOOTSTRAP_FILES_KEY] : [] ;
    }
    
    /**
     * Get a list of $_SERVER variable $name => $value pairs to be initialized
     * before the Yii app creation according to the {@see $testDirectory}.
     * 
     * @return array an empty array if no $_SERVER variables were defined in
     * {@see $_globalConfig} for the {@see testDirectory}
     */
    public function getServerVars()
    {
        return isset($this->getTestDirectoryConfig()[self::SERVER_VARS_KEY]) ? $this->getTestDirectoryConfig()[self::SERVER_VARS_KEY] : [];
    }
    
    /**
     * Get Yii app config according to the {@see $testDirectory}.
     * 
     * @return array
     * @throws InvalidConfigException no Yii app config found for the {@see $testDirectory}
     */
    public function getYiiAppConfig()
    {
        if (isset($this->getTestDirectoryConfig()[self::APP_KEY])) {
            return $this->getTestDirectoryConfig()[self::APP_KEY];
        } else {
            throw new InvalidConfigException("No config for Yii app found.", 50);
        }
    }
    
    /**
     * Set the {@see $_globalConfig} property.
     * 
     * If the config contains a path to a config file, its content is included
     * as an array.
     * @param string|array $config if a string, it must be a path to a file
     * returning an array. This array can not be empty.
     * The config array must follow this format:
     * - each key is a path to a directory in the tests tree;
     * - each value is a config array for this directory and sub (if the value is
     * a string, it must be a path to a file returning an array). This array can
     * contain 3 items - bootstrap files, server variables and Yii app config.
     * - the value of the 'bootstrap files' item must be an array containing the
     * path to one or more bootstrap files;
     * - the value of the 'server variables' item must be an array;
     * - the value of the 'yii app' item must be an array (any file path is
     * resolved to its content if it returns an array);
     * @throws InvalidParamException:
     * - $config param can not be empty;
     * - a $config param keys is not a path to an existing directory;
     * - a 'bootstrap files' item is not an array;
     * - a 'bootstrap files' item has an invalid file path;
     * - a 'server variables' item is not an array;
     * @see resolveConfig() for other InvalidParamException
     */
    public function setGlobalConfig($config)
    {
        $config = $this->resolveConfig($config);

        if ([] === $config) {
            throw new InvalidConfigException("Global config cannot be empty.", 03);
        }

        foreach ($config as $path => $data) {

            if (!is_dir($path)) {
                throw new InvalidConfigException("Each key of the configuration"
                        . " array must be a path to a directory of the tests tree. The path '$path' is invalid.", 04);
            }

            // a 'bootstrap files' key must point to an array valid filename(s)
            if (isset($data[self::BOOTSTRAP_FILES_KEY])) {

                if (is_array($data[self::BOOTSTRAP_FILES_KEY])) {
                    foreach ($data[self::BOOTSTRAP_FILES_KEY] as $bootstrapFile) {
                        // bootstrap file path must be valid
                        if (!is_file($bootstrapFile)) {
                            throw new InvalidConfigException("Invalid bootstrap file '$bootstrapFile'.", 05);
                        }
                    }
                } else {
                    throw new InvalidConfigException("Bootstrap files must be listed in an array.", 06);
                }

            }

            // a 'server variables' key must point to an array (server variable names or values are not checked)
            if (isset($data[self::SERVER_VARS_KEY])) {

                if (!is_array($data[self::SERVER_VARS_KEY])) {
                    throw new InvalidConfigException("Server vars must be listed in an array.", 07);
                }
            }

            // a 'yii app' key must point to an array
            if (isset($data[self::APP_KEY])) {
                
                if (!is_array($data[self::APP_KEY])) {
                    throw new InvalidConfigException("Yii app config must be listed in an array.", 08);
                }
                
                // each item can be a filename (a config file) or an array
                $mergedAppConfig = [];
                foreach ($data[self::APP_KEY] as $appConfig) {
                    // if a filename it must be a valid file returning an array
                    $appConfigArray = $this->resolveConfig($appConfig);
                    // appConfig items are merged in their order of appearance
                    $mergedAppConfig = \yii\helpers\ArrayHelper::merge($mergedAppConfig, $appConfigArray);
                }
                $config[$path][self::APP_KEY] = $mergedAppConfig;
            }
        }
        $this->_globalConfig = $config;
    }
    
    /**
     * Set the {@see $testDirectory} property.
     * 
     * @param string $filename
     * @throws InvalidConfigException filename does not exist or is not a directory
     */
    public function setTestDirectory($filename)
    {
        if (is_dir($filename)) {
            $this->testDirectory = $filename;
        } else {
            throw new InvalidConfigException("'$filename' is not a path to an existing directory.", 10);
        }
    }
    
    /**
     * Get the {@see $_testDirectoryConfig} property.
     * 
     * @return array
     */
    protected function getTestDirectoryConfig()
    {
        if (null === $this->_testDirectoryConfig) {
            $this->_testDirectoryConfig = $this->generateTestDirectoryConfig();
        }
        return $this->_testDirectoryConfig;
    }
    
    /**
     * Get the {@see $_globalConfig} property.
     * 
     * @return array
     * @throws InvalidConfigException $_globalConfig property has not been initialized
     */
    protected function getGlobalConfig()
    {
        if (null !== $this->_globalConfig) {
            return $this->_globalConfig;
        } else {
            throw new InvalidConfigException("No global config for Yii app. " . get_class() .
                    '::$globalConfig should have been initialized. See setGlobalConfig()', 20);
        }
    }
    
    /**
     * Generate a config to create a Yii app - including bootstrap files and
     * $_SERVER variables - according to the {@see $testDirectory}.
     * 
     * @return array
     */
    protected function generateTestDirectoryConfig()
    {
        $globalConfigMatchingPaths = $this->searchGlobalConfigMatchingPaths();
        sort($globalConfigMatchingPaths);
        $testCaseConfig = [];
        foreach ($globalConfigMatchingPaths as $globalConfigPath) {
            $testCaseConfig = \yii\helpers\ArrayHelper::merge($testCaseConfig, $this->globalConfig[$globalConfigPath]);
        }
        return $testCaseConfig;
    }
    
    /**
     * Search for keys in {@see $_globalConfig} that matches the path of the
     * {@see $testDirectory} property.
     * 
     * A matching path is equal to or an ancestor of {@see testDirectory}.
     *
     * @return array of paths
     */
    protected function searchGlobalConfigMatchingPaths()
    {
        if (null === $this->testDirectory) {
            throw new InvalidConfigException("Property \$testDirectory should have been initialized.", 30);
        }
        $matchingPaths = [];
        foreach ($this->globalConfig as $path => $value) {
            if (false !== strpos($this->testDirectory, rtrim($path, '/'))) {
                $matchingPaths[] = $path;
            }
        }
        return $matchingPaths;
    }
    
    /**
     * Return the $config parameter as an array.
     *
     * If the $config parameter is an array, it is returned as is. If it's a
     * string, the path to a file returning an array, the file content is
     * returned.
     *
     * @param string|array $config
     * @return array
     * @throws InvalidParamException:
     * - the $config parameter is neither an array nor a string;
     * - the $config parameter is a string which points to an invalid file i.e.
     * the file does not exist or does not return an array;
     */
    protected function resolveConfig($config)
    {
        if (is_array($config)) {
            return $config;
        } else if (is_string($config)) {
            if (is_file($config)) {
                $config = include $config;
                if (is_array($config)) {
                    return $config;
                } else {
                    throw new InvalidConfigException("Config file '' should return an array.", 40);
                }
            } else {
                throw new InvalidConfigException("Invalid config filename '$config'.", 41);
            }
        } else{
            throw new InvalidConfigException("Config must be an array or a string i.e. the path to a config file. ", 42);
        }
    }
}
