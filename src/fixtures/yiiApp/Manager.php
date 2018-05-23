<?php
namespace pyd\testkit\fixtures\yiiApp;

use pyd\testkit\interfaces\InterfaceYiiAppConfigProvider;
use yii\base\InvalidConfigException;

/**
 * Manage the Yii app instance to be used as a tests fixture.
 * 
 * @see $configProvider for Yii app config and $_SERVER variables to initialize
 * before creating the app.
 * 
 * ```php
 * $appManager = new Manager();
 * $appManager->setConfigProvider($configProvider);
 * $appManager->setServerVars();
 * $appManager->createYiiApp();
 * ...
 * $appManager->reset();
 * ```
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Manager extends \yii\base\Object
{
    /**
     * Class name of the Yii app to be created.
     * @var string
     */
    protected $appClass = '\yii\web\Application';
    /**
     * Backup of the $_SERVER initial state so it can be restored.
     * @see setServerVars()
     * @see resetServerVars()
     * @var array
     */
    protected $initialServerVars = [];
    /**
     * Provides config to create Yii app - including bootstrap files to load and
     * $_SERVER variables to initialize.
     * @var \pyd\testkit\fixtures\yiiApp\ConfigProvider
     */
    private $_configProvider;
    
    /**
     * Set the {@see $configProvider} property.
     * @param string|array|\pyd\testkit\interfaces\InterfaceYiiAppConfigProvider $configProvider
     */
    protected function setConfigProvider($configProvider)
    {
        $this->_configProvider = \yii\di\Instance::ensure($configProvider, '\pyd\testkit\interfaces\InterfaceYiiAppConfigProvider');
    }
    
    /**
     * Get the {@see $_configProvider} property.
     * 
     * @return \pyd\testkit\interfaces\InterfaceYiiAppConfigProvider
     * @throws InvalidConfigException property has not been initialized
     */
    protected function getConfigProvider()
    {
        if (null !== $this->_configProvider) {
            return $this->_configProvider;
        } else {
            throw new InvalidConfigException("Property " . get_class() . "::\$_configProvider should have been initialized.");
        }
    }
    
    /**
     * Set $_SERVER variables.
     */
    protected function setServerVars()
    {
        foreach ($this->getConfigProvider()->getServerVars() as $key => $value) {
            /**
             * When setting a variable that does not exist in the $_SERVER
             * (e.g. 'SERVER_NAME' in CLI), its initial value is set to
             * 'remove'. The @see resetServerVars() method will then
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
    
    /**
     * Restore $_SERVER to its initial state.
     */
    protected function resetServerVars()
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
     * Create the Yii application instance.
     */
    protected function createYiiApp()
    {
        $config = $this->getConfigProvider()->getYiiAppConfig();
        if (empty($config['class'])) $config['class'] = $this->appClass;
        \Yii::createObject($config);
    }
    
    /**
     * Destroy the Yii app instance by setting {@see \Yii::$app} to null.
     */
    protected function destroyYiiApp()
    {
        \Yii::$app = null;
    }
    
    /**
     * Reset Yii app instance and $_SERVER variables.
     */
    public function reset()
    {
        $this->resetServerVars();
        $this->destroyYiiApp();
        $this->setServerVars();
        $this->createYiiApp();
    }
}
