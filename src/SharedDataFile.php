<?php
namespace pyd\testkit;

use yii\base\UnknownPropertyException;

/**
 * Share data between php processes using a file.
 * 
 * @see \Fuz\Component\SharedMemory\SharedMemory
 *
 * @property boolean $testCaseStarted the TestCase::setUpBeforeClass has been
 * executed for the currently processed test case
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class SharedDataFile
{
    /**
     * @var \Fuz\Component\SharedMemory\SharedMemory
     */
    protected $adapter;

    public function __construct($storageFile)
    {
        $storage = new \Fuz\Component\SharedMemory\Storage\StorageFile($storageFile);
        $this->adapter = new \Fuz\Component\SharedMemory\SharedMemory($storage);
    }

    public function testCaseIsStarted()
    {
        return true === $this->adapter->get('testCaseStarted');
    }

    public function recordTestCaseStarted()
    {
        $this->adapter->set('testCaseStarted', true);
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
}
