<?php
namespace pyd\testkit\fixtures\base;

use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

/**
 * Create and destroy a Yii application instance used as a testing fixture.
 * Also handle application environment i.e. $_SERVER variables initialization
 * and bootstrap file(s) loading.
 *
 * Yii application config, $_SERVER variables to initialize an bootstrap file(s)
 * to load are provided by the @see $configProvider
 *
 * @see pyd\testkit\fixtures\AppConfig
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class App extends \yii\base\Object
{
    /**
     * @var string class name of the Yii app instance to be created
     * @todo with the web app some msg are displayed as html, with the console
     * app a 'user' component must be defined - it's not by default.
     */
    protected $appClassName = '\yii\web\Application';
    /**
     * @var \pyd\testkit\fixtures\AppConfig
     */
    protected $configProvider;
    /**
     * @var boolean
     * @see \pyd\testkit\base\TestCase::$shareYiiApp
     */
    protected $testCaseShareYiiApp;

    public function init()
    {
        if (null === $this->configProvider) {
            throw new InvalidConfigException('Property $configProvider should be initialized.');
        }
    }

    /**
     * @return \pyd\testkit\fixtures\AppConfig
     * @see $configProvider
     */
    public function getConfigProvider()
    {
        return $this->configProvider;
    }

    /**
     * Set $_SERVER variables, load bootstrap files and create Yii app.
     */
    public function create()
    {
        $this->setServerVars($this->configProvider->getServerVars());
        $this->loadBootstrapFiles($this->configProvider->getBootstrapFiles());
        $this->createYiiApp($this->configProvider->getAppConfig());
    }

    /**
     * Destroy Yii application instance.
     */
    public function destroy()
    {
        \Yii::$app = null;
    }

    /**
     * Initialize $_SERVER variables.
     *
     * @param array $serverVars ['name' => $value', 'othername' => $otherValue,...]
     */
    protected function setServerVars(array $serverVars)
    {
        foreach ($serverVars as $key => $value) {
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Load bootstrap files.
     *
     * @param array $bootstrapFiles ['/path/to/bootstrapFileOne.php', '/path/to/bootstrapFileTwo.php', ...]
     */
    protected function loadBootstrapFiles(array $bootstrapFiles)
    {
        foreach ($bootstrapFiles as $bootstrapFile) {
            require_once $bootstrapFile;
        }
    }

    /**
     * Create Yii app.
     *
     * @param array $appConfig
     */
    protected function createYiiApp(array $appConfig)
    {
        if (empty($appConfig['class'])) $appConfig['class'] = $this->appClassName;
        \Yii::createObject($appConfig);
    }

    /**
     * Setter for @see $configProvider.
     *
     * @param array $config
     */
    protected function setConfigProvider(array $config)
    {
        $this->configProvider = \Yii::createObject($config);
    }
}
