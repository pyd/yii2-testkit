<?php
namespace pyd\testkit\interfaces;

/**
 * Interface for class that manages Yii app as a fixture.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
interface InterfaceYiiAppManager
{
    public function create();
    
    public function destroy();
}
