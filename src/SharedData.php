<?php
namespace pyd\testkit;

use yii\base\UnknownPropertyException;

/**
 * Share data between php processes.
 *
 * @see \Fuz\Component\SharedMemory\SharedMemory
 *
 * @property boolean $mainProcessStarted
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class SharedData extends \yii\base\Object
{
    /**
     * @var \Fuz\Component\SharedMemory\SharedMemory
     */
    protected $adapter;

    /**
     * @see $adapter
     * @return \Fuz\Component\SharedMemory\SharedMemory
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
    /**
     * @return boolean 
     */
    public function getMainProcessStarted()
    {
        return $this->adapter->get('mainProcessStarted');
    }

    /**
     * 
     */
    public function setMainProcessStarted()
    {
        $this->adapter->set('mainProcessStarted', true);
    }

    public function getLoadedDbTables()
    {
        return $this->adapter->get('loadedDbTables', []);
    }

    public function setLoadedDbTables(array $loadedDbTableClassNames)
    {
        $this->adapter->set('loadedDbTables', $loadedDbTableClassNames);
    }

    public function destroy()
    {
        $this->adapter->destroyStorage();
    }

    protected function setAdapter(array $config)
    {
        $storage = new \Fuz\Component\SharedMemory\Storage\StorageFile($config['storageFile']);
        $this->adapter = new \Fuz\Component\SharedMemory\SharedMemory($storage);
    }
}
