<?php
namespace pyd\testkit\fixtures\yiiApp;

/**
 * Interface for a class providing config to create a Yii app.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
interface ConfigProvider
{
    /**
     * Get $_SERVER variables to be initialized before creating the Yii app.
     */
    public function getServerVars();
    /**
     * Get config to create Yii app.
     */
    public function getYiiAppConfig();
}
