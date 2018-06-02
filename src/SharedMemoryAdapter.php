<?php
namespace pyd\testkit;

/**
 * Share data between php processes using the php-shared-memory lib.
 * 
 * https://github.com/Ninsuo/php-shared-memory
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class SharedMemoryAdapter implements SharedData
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
     * Remove a variable.
     * 
     * @param string $name variable name
     */
    public function remove($name)
    {
        $this->sharedMemory->remove($name);
    }
    
    /**
     * Destroy all variables.
     */
    public function destroy()
    {
        $this->sharedMemory->lock();
        $this->sharedMemory->setData(new \stdClass());
        $this->sharedMemory->unlock();
    }
}
