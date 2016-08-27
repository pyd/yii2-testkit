<?php
namespace pyd\testkit\fixtures;

use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

/**
 * Manage Yii app creation and deletion at the test case level.
 *
 * Yii app configuration is provided by @see $configProvider.
 * If configuration contains bootstrap files to load and|or $_SERVER variables
 * to initialize, this will be done before the Yii app instantiation.
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
     * Handler for the 'setUpBeforeClass' event.
     *
     * Yii app is created.
     *
     * @param string $testCaseClassName class name of the currently executed
     * test case
     */
    public function onSetUpBeforeClass($testCaseClassName)
    {
        $this->testCaseShareYiiApp = $testCaseClassName::$shareYiiApp;
        $this->create();
    }

    /**
     * Handler for the 'tearDown' event.
     *
     * If test method is in isolation, just wait for it's deletion in the
     * onTearDownAfterClass() method.
     */
    public function onTearDown(\pyd\testkit\base\TestCase $testCase)
    {
        if (!$testCase->isInIsolation()) {
            if (null !== \Yii::$app && !$this->testCaseShareYiiApp) {
                $this->destroy();
            }
            if (null === \Yii::$app) {
                $this->create();
            }
        }
    }

    /**
     * Handler for the 'tearDownAfterClass' event.
     *
     * Yii app is destroyed.
     */
    public function onTearDownAfterClass()
    {
        $this->destroy();
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
     * Delete Yii application instance.
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
