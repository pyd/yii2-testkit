<?php
namespace pyd\testkit;

/**
 * Manage Yii app.
 *
 * This class mostly handles the Yii app creation. In order to do that it will
 * eventually load bootstrap files and set server variables.
 *
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AppFixture extends \yii\base\Object
{
    /**
     * @var string
     */
    public $appClassName = '\yii\web\Application';

    /**
     * @var \pyd\testkit\interfaces\AppConfigProviderInterface
     */
    protected $configProvider;

    public function init()
    {
        if (null === $this->configProvider) {
            throw new \yii\base\InvalidConfigException(get_class($this) . '::$configProvider must be initialized.');
        }
    }

    /**
     * Create Yii application instance @see $appClassname.
     *
     * If bootstrap files to load and/or $_SERVER variables to initialized are
     * returned by the @see $configProvider, it will be done before the Yii app
     * creation.
     *
     * @param string $testCaseDirPath path to the parent directory of the
     * currently executed test case
     */
    public function create($testCaseDirPath)
    {
        $this->loadBootstrapFiles($this->configProvider->getBootstrapFiles($testCaseDirPath));
        $this->setServerVariables($this->configProvider->getServerVariables($testCaseDirPath));
        $this->createYiiApp($this->configProvider->getAppConfig($testCaseDirPath));
    }

    public function clear()
    {
        \Yii::$app = null;
        // reset server variables ?
    }

    /**
     * Check if Yii application was created.
     *
     * @return boolean
     */
    public function appCreated()
    {
        return null !== \Yii::$app;
    }

    /**
     * Initialize $_SERVER variables.
     *
     * @param array $variables ['name' => $value', 'othername' => $otherValue,...]
     */
    public function setServerVariables(array $variables)
    {
        foreach ($variables as $key => $value) {
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Load bootstrap files.
     *
     * @param array $bootstrapFiles ['/path/to/bootstrapFileOne.php', '/path/to/bootstrapFileTwo.php', ...]
     */
    public function loadBootstrapFiles(array $bootstrapFiles)
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
    public function createYiiApp(array $appConfig)
    {
        if (empty($appConfig['class'])) $appConfig['class'] = $this->appClassName;
        \Yii::createObject($appConfig);
    }

    /**
     * @param string|array|\pyd\testkit\interfaces\AppConfigProviderInterface $configProvider
     */
    protected function setConfigProvider($configProvider)
    {
        $this->configProvider = \yii\di\Instance::ensure($configProvider, '\pyd\testkit\interfaces\AppConfigProviderInterface');
    }
}
