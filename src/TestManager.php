<?php
namespace pyd\testkit;

/**
 * @brief ...
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class TestManager extends \yii\base\Object
{
    /**
     * This method is called by @see \PHPUnit_Framework_TestCase::setUpBeforeClass().
     *
     * @param string $testCaseClassName class name - with namespace - of the
     * currently processed test case
     */
    public function onSetUpBeforeClass($testCaseClassName)
    {
        if ($testCaseClassName !== $this->getSharedMemory()->testCaseClassName) {
            $this->onTestCaseStart($testCaseClassName);
        }
    }

    /**
     * This method is called by @see \PHPUnit_Framework_TestCase::setUp().
     */
    public function onSetUp()
    {
        if (null === \Yii::$app) $this->getAppManager()->create();
    }

    /**
     * This method is called by @see \PHPUnit_Framework_TestCase::tearDownAfterClass().
     */
    public function onTearDownAfterClass()
    {
        if (getmygid() === $this->getSharedMemory()->testCaseStartPid) {
            $this->onTestCaseEnd();
        }
    }

    /**
     * @var \pyd\testkit\SharedMemory
     */
    private $_sharedMemory;

    /**
     * This object allows sharing data between different PHP processes - when a
     * test case or a test method uses 'isolation'.
     *
     * @return \pyd\testkit\SharedMemory
     */
    public function getSharedMemory()
    {
        if (null === $this->_sharedMemory) {
            $storage = new \Fuz\Component\SharedMemory\Storage\StorageFile(\Yii::getAlias('@tests').'/shared_data');
            $this->_sharedMemory = new SharedMemory($storage);
        }
        return $this->_sharedMemory;
    }

    /**
     * @var \pyd\testkit\AppManager
     */
    private $_appManager;

    public function getAppManager()
    {
        if (null === $this->_appManager) {
            $this->_appManager = new AppManager();
        }
        return $this->_appManager;
    }


    /**
     * This method is executed once when a test case starts - before test methods.
     */
    protected function onTestCaseStart($testCaseClassName)
    {
        $this->getSharedMemory()->testCaseClassName = $testCaseClassName;
    }

    /**
     * This method is executed once when a test case ends - after test methods.
     */
    protected function onTestCaseEnd()
    {
        $this->getSharedMemory()->testCaseClassName = null;
        $this->getSharedMemory()->testCaseStartPid = null;
    }
    
}
