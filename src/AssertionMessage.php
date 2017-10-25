<?php
namespace pyd\testkit;

/**
 * Store and provide generic failure messages for assertion methods.
 *
 * <code>
 * // AssertionMessage::get() will return the message set by the hasFocus()
 * // method
 * $this->assertTrue($form->username->hasFocus(), AssertionMessage::get());
 * </code>
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class AssertionMessage
{
    /**
     * @var string message container
     */
    protected static $msg = '';

    /**
     * Set an assertion message.
     * Any previous message will be overwritten.
     *
     * @param string $msg
     */
    public static function set($msg)
    {
        self::$msg = "\n$msg";
    }

    /**
     * Return the stored assertion message.
     *
     * If no message is stored, the default "No assertion message available."
     * message is returned.
     *
     * @return string
     */
    public static function get()
    {
        if (self::isEmpty()) {
            return "No assertion message available.";
        } else {
            $msg = self::$msg;
            self::clear();
            return  $msg;
        }
    }

    /**
     * Add an assertion message.
     * Any existing message is preserved.
     *
     * @param string $msg
     * @param boolean $newLine add a new line "\n" before the new message
     */
    public static function add($msg, $newLine = false)
    {
        if ($newLine) {
            self::$msg .= "\n$msg";
        } else {
            self::$msg .= (self::isEmpty()) ? $msg : " $msg";
        }
    }

    /**
     * Clear assertion message.
     */
    public static function clear()
    {
        self::$msg = '';
    }

    /**
     * @return boolean no assertion message stored
     */
    public static function isEmpty()
    {
        return '' === self::$msg;
    }
}
