<?php
namespace pyd\testkit\interfaces;

/**
 * Interface for class that provides config to create Yii app.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
interface InterfaceYiiAppConfigProvider
{
    public function getBootstrapFiles();
    
    public function getServerVars();
    
    public function getYiiAppConfig();
}
