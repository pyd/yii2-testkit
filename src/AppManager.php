<?php
namespace pyd\testkit;

/**
 * Yii application manager.
 *
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AppManager extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\interfaces\AppConfigInterface
     */
    protected $configManager;

    public function init()
    {
        if (null === $this->configManager) $this->setConfigManager(AppConfigByTestCasePath::className());
    }

    public function create()
    {
        $this->setServerVariables($this->configManager->getServerVariables());
        $this->loadBootstrapFiles($this->configManager->getBootstrapFiles());
        $this->createYiiApp($this->configManager->getYiiAppConfig());
    }

    public function clear()
    {
        \Yii::$app = null;
        // reset server variables ?
    }

    protected function setServerVariables(array $variables)
    {
        // save server var states with SharedMemory if reset is implemented
    }

    protected function loadBootstrapFiles(array $bootstrapFiles)
    {

    }

    protected function createYiiApp(array $config)
    {

    }

    /**
     * Internal setter.
     *
     * @param string|array|\pyd\testkit\interfaces\AppConfigInterface $configManager
     */
    protected function setConfigManager($configManager)
    {
        $this->configManager = \yii\di\Instance::ensure($configManager, '\pyd\testkit\interfaces\AppConfigInterface');
    }
}
