<?php
namespace pyd\testkit;

use pyd\testkit\Testkit;

/**
 * Testkit application.
 * 
 * @property \pyd\testkit\fixtures\yiiApp\Manager $fixtureApp
 * @property \pyd\testkit\fixtures\db\Manager $fixtureDb
 * @property \pyd\testkit\events\Mediator $eventMediator
 * @property \pyd\testkit\SharedData $sharedData
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Application extends \yii\di\ServiceLocator
{
    public function init()
    {
        parent::init();
        Testkit::$app = $this;
    }
    
    public function run()
    {
        
    }
}
