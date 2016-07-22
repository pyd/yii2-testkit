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
class AppManager extends \yii\base\Object
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

    public function create()
    {
        $this->setServerVariables($this->configProvider->getServerVariables());
        $this->loadBootstrapFiles($this->configProvider->getBootstrapFiles());
        $this->createYiiApp($this->configProvider->getAppConfig());
    }

    public function clear()
    {
        \Yii::$app = null;
        // reset server variables ?
    }

    /**
     * Initialize $_SERVER variables.
     *
     * @param array $variables ['name' => $value', 'othername' => $otherValue,...]
     */
    protected function setServerVariables(array $variables)
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
     * @param string|array|\pyd\testkit\interfaces\AppConfigProviderInterface $configProvider
     */
    protected function setConfigProvider($configProvider)
    {
        $this->configProvider = \yii\di\Instance::ensure($configProvider, '\pyd\testkit\interfaces\AppConfigProviderInterface');
    }
}
