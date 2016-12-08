<?php
namespace pyd\testkit;

/**
 * @brief ...
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Log
{
    protected static $filename = '/var/www/html/py.delettre.me/tests/testkit.log';

    public static function add($text) {
        $fh = fopen(self::$filename, 'a') or die("can't open file");
        fwrite($fh, microtime() . " " . $text . " in pid " .  getmypid(). "\n");
        fclose($fh);
    }
}
