<?php
namespace pyd\testkit;

/**
 * Share data between the main php process - created when the phpunit command
 * is launched - and each php process created to execute a test method in
 * isolation.
 * 
 * This is an adampter for the  {@see \Fuz\Component\SharedMemory\SharedMemory}
 * class.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class SharedDataSharedMemoryAdapter implements SharedDataInterface
{
    /**
     * @var \Fuz\Component\SharedMemory\SharedMemory
     */
    private $sharedMemory;
    
    public function __construct(\Fuz\Component\SharedMemory\SharedMemory $sharedMemory)
    {
        $this->sharedMemory = $sharedMemory;
    }
    
    /**
     * Get a variable value.
     * 
     * @param string $name variable name
     * @param mixed $default value to be returned when the variable does not exist
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return $this->sharedMemory->get($name, $default);
    }
    
    /**
     * Set a variable value.
     * 
     * @param string $name variable name
     * @param mixed $value variable value
     */
    public function set($name, $value)
    {
        $this->sharedMemory->set($name, $value);
    }
    
    /**
     * Unset all variables.
     */
    public function unsetAll()
    {
        $this->sharedMemory->lock();
        $this->sharedMemory->setData(new \stdClass());
        $this->sharedMemory->unlock();
    }
}
