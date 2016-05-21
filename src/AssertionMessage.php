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
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AssertionMessage
{
    /**
     * @var string message container
     */
    protected static $msg;

    /**
     * Store a message. Any existing content will be overwritten.
     *
     * @param string $msg
     */
    public static function set($msg)
    {
        self::$msg = "\n$msg";
    }

    /**
     * Return the currently stored message and remove it.
     *
     * @return string
     */
    public static function get()
    {
        $msg = self::$msg;
        if (null === $msg) {
            return "No assertion message available.";
        }
        self::$msg = null;
        return $msg;
    }

    /**
     * Add a message. Any existing content will be preserved.
     *
     * @param string $msg
     * @param boolean $newLine add a new line "\n" before the added message
     */
    public static function add($msg, $newLine = false)
    {
        $msg = $newLine ? "\n$msg" : " $msg";
        self::$msg .= $msg;
    }

    /**
     * Clear message.
     */
    public static function clear()
    {
        self::$msg = '';
    }
}
