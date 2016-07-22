<?php
namespace pyd\testkit;

use yii\base\InvalidConfigException;

/**
 * Provide configuration for Yii app creation, including bootstrap files to load
 * and server variable to initialize.
 *
 * ```php
 * $configProvider = new AppConfigProviderByPath(['globalConfig' => $config]);
 * $testCaseDirPath = '/path/to/testcase/parent/directory';
 * $bootstrapFiles = $configProvider->getBootstrapFiles($testCaseDirPath);
 * $serverVars = $configProvider->getServerVars($testCaseDirPath);
 * $appConfig = $configProvider->getAppConfig($testCaseDirPath);
 * ```
 *
 * The goal is to work on a global configuration array and extract the relevant
 * parts based on the path to the currently executed test case. This can be used
 * with a basic template app or an advanced one as well.
 * The configuration array should look like:
 *
 * ```php
 * $basePath = '/var/www/html/domain.com';
 * $bootstrapFilesKey = AppConfigProviderByPath::BOOTSTRAP_FILES_KEY;
 * $serverVarsKey = AppConfigProviderByPath::SERVER_VARS_KEY;
 * $appKey = AppConfigProviderByPath::APP_KEY;
 * $config = [
 *
 *      $basePath . '/tests' => [
 *          $bootstrapFilesKey => [$basePath . '/common/config/bootstrap.php'],
 *          $appKey => [
 *              $basePath . '/common/config/main.php',
 *              $basePath . '/common/config/main-local.php'
 *          ]
 *      ],
 *
 *      $basePath . '/tests/frontend' => [
 *          $bootstrapFilesKey => [$basePath . '/frontend/config/bootstrap.php'],
 *          $serverVarsKey => [
 *              'SERVER_NAME' => 'http://domain.com',
 *              'SCRIPT_NAME' => 'http://domain.com/index-test.php',
 *              'SCRIPT_FILENAME' => $basePath . '/frontend/web/index-test.php',
 *          ],
 *          $appKey => [
 *              $basePath . '/frontend/config/main.php',
 *              $basePath . '/frontend/config/main-local.php'
 *          ]
 *      ],
 *
 *      $basePath . '/tests/backend' => [
 *          $bootstrapFilesKey => [$basePath . '/backend/config/bootstrap.php'],
 *          $serverVarsKey => [
 *              'SERVER_NAME' => 'http://backend.domain.com',
 *              'SCRIPT_NAME' => 'http://backend.domain.com/index-test.php',
 *              'SCRIPT_FILENAME' => $basePath . '/backend/web/index-test.php',
 *          ],
 *          $appKey => [
 *              $basePath . '/backend/config/main.php',
 *              $basePath . '/backend/config/main-local.php'
 *          ]
 *      ],
 *      $basePath . '/tests/common' => [
 *          ...
 *      ]
 * ]
 * ```
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AppConfigProviderByPath extends \yii\base\Object
{
    /**
     * Config array key for bootstrap files
     */
    const BOOTSTRAP_FILES_KEY = 'bootstrap-files';
    /**
     * Config array key for server variables
     */
    const SERVER_VARS_KEY = 'server-vars';
    /**
     * Config array key for app config
     */
    const APP_KEY = 'app';

    /**
     * @var array global - for all tests - configuration
     */
    protected $globalConfig;

    public function init()
    {
        if (null === $this->globalConfig) {
            throw new InvalidConfigException(get_class($this) . '::$globalConfig must be initialized.', 10);
        }
    }

    /**
     * Return bootstrap files to load according to the currently executed
     * test case parent directory.
     *
     * @param string $testCaseDirPath path to the parent directory of the
     * currently executed test case
     * @return array ['/path/to/bootstrap/file/one.php', '/path/to/bootstrap/file/two.php',...]
     */
    public function getBootstrapFiles($testCaseDirPath)
    {
        $configByPath = $this->getConfigByPath($testCaseDirPath);
        if (isset($configByPath[self::BOOTSTRAP_FILES_KEY])) {
            return $configByPath[self::BOOTSTRAP_FILES_KEY];
        }
        return [];
    }

    /**
     * Return server variables to be initialized according to the currently executed
     * test case.
     *
     * @return array ['serverVarName' => $serverVarNameValue, 'otherServerVarName' => $otherServerVarNameValue, ...]
     */
    public function getServerVars($path)
    {
        $config = $this->getConfigByPath($path);
        if (isset($config[self::SERVER_VARS_KEY])) {
            return  $config[self::SERVER_VARS_KEY];
        }
        return [];
    }

    /**
     * Return the configuration array to be used to create the Yii app according
     * to the currently executed test case.
     *
     * @return array
     */
    public function getAppConfig($path)
    {
        $config = $this->getConfigByPath($path);
        if (isset($config[self::APP_KEY]) && !empty($config[self::APP_KEY])) {
            return $config[self::APP_KEY];
        }
        return [];
    }

    /**
     * Set config for all tests @see $globalConfig
     *
     * Config checks:
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
    protected function setglobalConfig($config)
    {
        $config = $this->getConfigAsArray($config);

        if (empty($config)) {
            throw new InvalidConfigException("Config as an array cannot be empty.");
        }

        foreach ($config as $path => &$data) {

            if (!is_dir($path)) {
                throw new InvalidConfigException("Each key of the configuration"
                        . " array must be a path to a tests directory. The path '$path' is invalid.");
            }

            // bootstrap files config check
            if (isset($data[self::BOOTSTRAP_FILES_KEY])) {

                if (is_array($data[self::BOOTSTRAP_FILES_KEY])) {
                    foreach ($data[self::BOOTSTRAP_FILES_KEY] as $bootstrapFile) {
                        if (!is_file($bootstrapFile)) {
                            throw new InvalidConfigException("Invalid bootstrap file '$bootstrapFile'.");
                        }
                    }
                } else {
                    throw new InvalidConfigException("Bootstrap files must be listed in an array.");
                }

            }

            // server vars config check
            if (isset($data[self::SERVER_VARS_KEY])) {

                if (is_array($data[self::SERVER_VARS_KEY])) {
                    foreach ($data[self::SERVER_VARS_KEY] as $key => $value) {
                        $_SERVER[$key] = $value;
                    }
                } else {
                    throw new InvalidConfigException("Server vars must be listed in an array.");
                }
            }

            // app config check
            if (isset($data[self::APP_KEY])) {
                $data[self::APP_KEY] = $this->getConfigAsArray($data[self::APP_KEY]);
            }
        }

        $this->globalConfig = $config;
    }

    private $lastTestCaseDirPath;
    private $lastConfigByPath;

    /**
     * Extract and merge configs - from @see $globalConfig - that match the
     * $testCaseDirPath argument.
     *
     * @param string $testCaseDirPath path to the parent directory of the
     * currently executed test case
     * @return array
     */
    protected function getConfigByPath($testCaseDirPath)
    {
        if ($testCaseDirPath !== $this->lastTestCaseDirPath) {

            if (!is_dir($testCaseDirPath)) {
                throw new \yii\base\InvalidParamException("Path to test case directory '$testCaseDirPath' is invalid.");
            }

            $configByPath = [];
            $matchingConfigPaths = $this->getMatchingConfigPaths($testCaseDirPath);
            sort($matchingConfigPaths);


            foreach ($matchingConfigPaths as $matchingConfigPath) {
                $configByPath = \yii\helpers\ArrayHelper::merge($configByPath, $this->globalConfig[$matchingConfigPath]);
            }

            $this->lastTestCaseDirPath = $testCaseDirPath;
            $this->lastConfigByPath = $configByPath;
        }

        return $this->lastConfigByPath;
    }

    /**
     * Return config paths, i.e. keys of the @see $globalConfig, that are
     * ancestors of the $path argument or equal to it.
     *
     * @param string $testCaseDirPath path to the parent directory of the
     * currently executed test case
     * @return array [$path, ...]
     */
    protected function getMatchingConfigPaths($testCaseDirPath)
    {
        $matchingConfigPaths = [];
        foreach (array_keys($this->globalConfig) as $configPath) {
            if (false !== strpos($testCaseDirPath, rtrim($configPath, '/'))) {
                $matchingConfigPaths[] = $configPath;
            }
        }
        return $matchingConfigPaths;
    }

    /**
     * Return config as an array.
     *
     * @param string|array $config
     * @return array
     * @throws InvalidConfigException config cannot be resolved as an array
     */
    protected function getConfigAsArray($config)
    {
        if (is_string($config)) {
            if (is_file($config)) {
                $config = include $config;
            } else {
                throw new InvalidConfigException("Config file '$config' does not exist.");
            }
        }

        if(is_array($config)){
            return $config;
        }

        throw new InvalidConfigException("Config must be an array or the path to a file returning an array.", 40);
    }
}
