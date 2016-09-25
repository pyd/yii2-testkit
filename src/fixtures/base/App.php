<?php
namespace pyd\testkit\fixtures\base;

use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

/**
 * Manage Yii app as a testing fixture.
 *
 * @see pyd\testkit\fixtures\AppConfig for Yii app configuration.
 * AppConfig class can also provide a list of bootstrap files to be loaded
 * and|or $_SERVER variables to be initialized before the Yii app creation.
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
     * A Yii app instance is created when a test case starts or before a test
     * method in isolation.
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
     * If the test method was executed in isolation, the Yii app will be
     * destroyed in the @see onTearDownAfterClass() handler.
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
     * The Yii app instance is destroyed at the end of a test case or after
     * a test method in isolation.
     */
    public function onTearDownAfterClass($testCaseClassName, $testCaseEnd)
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
