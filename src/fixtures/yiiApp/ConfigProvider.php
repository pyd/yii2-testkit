<?php
namespace pyd\testkit\fixtures\yiiApp;

/**
 * interface for Yii app config provider classes.
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
