<?php
namespace pyd\testkit;

/**
 * Share data between the main php process - created when the phpunit command
 * is launched - and each php process created to execute a test method in
 * isolation.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
interface SharedDataInterface
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
     * Unset all variables.
     */
    public function unsetAll();
}
