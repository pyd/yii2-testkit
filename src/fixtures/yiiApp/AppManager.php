<?php
namespace pyd\testkit\fixtures\yiiApp;

use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

/**
 * Manage Yii application - as a fixture.
 * 
 * In order to access some components like 'db' 'urlManager'... a Yii application
 * instance have to be created. Additionally, it may be necessary to set some
 * $_SERVER variables and load some bootstrap files.
 * 
 * @see $configProvider provides configuration for the Yii application, bootstrap
 * files and $_SERVER variables.
 * 
 * When a Yii app is destroyed, the $_SERVER is restored to its initial state.
 * @warning Unloading bootstrap files is not possible in a generic way. If you
 * have to load different bootstrap files you need to implement a method to
 * 'undo' the bootstrap files job.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class AppManager extends \yii\base\Object
{
    /**
     * @var string class of the Yii app instance to be created
     */
    protected $appClassName = '\yii\web\Application';
    /**
     * @var \pyd\testkit\fixtures\yiiApp\ObserverConfigProvider
     */
    protected $configProvider;
    /**
     * @var array store $_SERVER variables before modifications
     */
    protected $initialServerVars = [];

    /**
     * Eager instantiation for the @see $configProvider.
     */
    public function init()
    {
        if (null === $this->configProvider) {
            throw new InvalidConfigException('Property $configProvider should be initialized.');
        }
    }

    /**
     * @return \pyd\testkit\fixtures\yiiApp\ObserverConfigProvider
     */
    public function getConfigProvider()
    {
        return $this->configProvider;
    }
    
    /**
     * @param string|array|callable @see \Yii::createObject()
     */
    protected function setConfigProvider($type)
    {
        $this->configProvider = \Yii::createObject($config);
    }

    /**
     * Create the Yii app instance, eventually after setting some $_SERVER
     * variables and loading some bootstrap files.
     * 
     * @see setServerVars()
     * @see loadBootstrapFiles()
     * @see createYiiApp()
     */
    public function create()
    {
        $this->setServerVars($this->configProvider->getServerVars());
        $this->loadBootstrapFiles($this->configProvider->getBootstrapFiles());
        $this->createYiiApp($this->configProvider->getAppConfig());
    }

    /**
     * Destroy the Yii application instance and restore the $_SERVER to its
     * initial state.
     */
    public function destroy()
    {
        \Yii::$app = null;
        $this->restoreInitialServerVars();
    }

    /**
     * Set $_SERVER variables.
     * 
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
     * 
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
     * Create the Yii application only i.e. without setting the $_SERVER
     * variables neither loading bootstrap files.
     * 
     * @see create() if you want to set the $_SERVER variables and load the
     * bootstrap files returned by the config provider. 
     *
     * @param array $appConfig
     */
    protected function createYiiApp(array $appConfig)
    {
        if (empty($appConfig['class'])) $appConfig['class'] = $this->appClassName;
        \Yii::createObject($appConfig);
    }

    
}
