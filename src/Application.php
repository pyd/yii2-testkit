<?php
namespace pyd\testkit;

use pyd\testkit\Testkit;

/**
 * Testkit application.
 * 
 * @property-read \pyd\testkit\events\Mediator $eventMediator
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
