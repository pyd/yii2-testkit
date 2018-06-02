<?php
namespace pyd\testkit;

/**
 * Interface for classes which instances can share data from differenr php
 * processes.
 * 
 * The goal is to store data when in the main php process (phpunit command) and
 * read them when in an isolated test and reciprocally.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
interface SharedData
{
    /**
     * Set a variable value.
     * 
     * @param string $name variable name
     * @param mixed $value variable value
     */
    public function set($name, $value);
    
    /**
     * Get a variable value.
     * 
     * @param string  $name variable name
     * @param mixed $default value to be returned when the variable does not
     * exist
     */
    public function get($name, $default = null);
    
    /**
     * Remove a variable.
     * 
     * @param string $name variable name
     */
    public function remove($name);
    
    /**
     * Destroy shared data storage.
     */
    public function destroy();
}
