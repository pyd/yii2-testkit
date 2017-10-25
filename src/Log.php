<?php
namespace pyd\testkit;

/**
 * @brief ...
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Log
{
    protected static $filename = '/var/www/html/py.delettre.me/tests/testkit.log';

    public static function add($text) {
        $fh = fopen(self::$filename, 'a') or die("can't open file");
        if (false === $fh) {
            throw new \LogicException("Failed opening file " . self::$filename);
        }
        fwrite($fh, microtime() . " " . $text . " in pid " .  getmypid(). "\n");
        fclose($fh);
    }
}
