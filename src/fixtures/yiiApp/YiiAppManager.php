<?php
namespace pyd\testkit\fixtures\yiiApp;

use pyd\testkit\interfaces\InterfaceYiiAppConfigProvider;
use yii\base\InvalidConfigException;

/**
 * Manage a Yii application to be used as a tests fixture.
 * 
 * Some Yii app components are required when testing like 'db', 'urlManager'...
 * In addition to the creation of the Yii app instance, this class alows you to
 * load bootstrap files and initialize $_SERVER variables.
 * Config for the Yii app, bootstrap files and $_SERVER variables is provided by
 * the {@see $configProvider}.
 * 
 * ```php
 * $appManager = new YiiAppManager();
 * $appManager->setConfigProvider($configProvider);
 * $appManager->create();
 * ...
 * $appManager->destroy();
 * 
 * @see \pyd\testkit\fixtures\yiiApp\YiiAppConfigProvider
 * ```
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class YiiAppManager extends \yii\base\Object implements \pyd\testkit\interfaces\InterfaceYiiAppManager
{
    /**
     * Class name of the Yii app to be created.
     * @var string
     */
    protected $appClass = '\yii\web\Application';
    /**
     * Backup of the $_SERVER initial state.
     * @see setServerVars()
     * @see restoreInitialServerVars()
     * @var array
     */
    protected $initialServerVars = [];
    /**
     * Provides config to create Yii app - including bootstrap files to load and
     * $_SERVER variables to initialize.
     * @var \pyd\testkit\fixtures\yiiApp\ObserverConfigProvider
     */
    protected $configProvider;
    
    /**
     * Set the {@see $configProvider} property.
     * @param string|array|\pyd\testkit\interfaces\InterfaceYiiAppConfigProvider $configProvider
     */
    public function setConfigProvider($configProvider)
    {
        $this->configProvider = \yii\di\Instance::ensure($configProvider, '\pyd\testkit\interfaces\InterfaceYiiAppConfigProvider');
    }
    
    /**
     * Create the Yii application instance.
     * This method will also load bootstrap files and initialize $_SERVER
     * variables according to the config provider.
     * @see setServerVars()
     * @see loadBootstrapFiles()
     * @see createYiiApp()
     * @throws InvalidConfigException property {@see $configProvider} has not
     * been initialized 
     */
    public function create()
    {
        if (null === $this->configProvider) {
            throw new InvalidConfigException("Property " . get_class() . "::\$configProvider should have been initialized.");
        }
        $this->setServerVars($this->configProvider->getServerVars());
        $this->loadBootstrapFiles($this->configProvider->getBootstrapFiles());
        $this->createYiiApp($this->configProvider->getAppConfig());
    }
    
    /**
     * Destroy the Yii application instance and restore the $_SERVER to its
     * initial state.
     * @see restoreInitialServerVars()
     */
    public function destroy()
    {
        \Yii::$app = null;
        $this->restoreInitialServerVars();
    }
    
    /**
     * Set $_SERVER variables.
     * If the $serverVars param is an empty array, nothing is set.
     * 
     * @param array $serverVars ['name' => $value', 'othername' => $otherValue,...]
     */
    protected function setServerVars(array $serverVars)
    {
        if ([] !== $serverVars) {
            
            foreach ($serverVars as $key => $value) {
                /**
                 * When setting a variable that does not exist in the $_SERVER
                 * (e.g. 'SERVER_NAME' in CLI), its initial value is set to
                 * 'remove'. The @see restoreInitialServerVars() method will then
                 * know that the variable cannot be restored but must be removed.
                 */
                if (!array_key_exists($key, $_SERVER)) {
                    $initialValue = 'remove';
                } else {
                    $initialValue = $_SERVER[$key];
                }
                $this->initialServerVars[$key] = $initialValue;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    /**
     * Restore $_SERVER to its initial state.
     */
    protected function restoreInitialServerVars()
    {
        if ([] !== $this->initialServerVars) {
            
            foreach ($this->initialServerVars as $key => $value) {
                /**
                 * If a $_SERVER variable has the 'remove' value, it must be
                 * removed.
                 * @see setServerVars()
                 */
                if ('remove' === $value) {
                   unset($_SERVER[$key]);  
                } else {
                    $_SERVER[$key] = $value;
                }
            }
            $this->initialServerVars = [];
        }
    }
    
    /**
     * Load bootstrap files.
     * If the $bootstrapFiles array is empty, nothing happens.
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
     * Create the Yii application instance.
     *
     * @param array $appConfig
     */
    protected function createYiiApp(array $appConfig)
    {
        if (empty($appConfig['class'])) $appConfig['class'] = $this->appClassName;
        \Yii::createObject($appConfig);
    }
}
