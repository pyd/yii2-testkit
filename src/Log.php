<?php
namespace pyd\testkit;

/**
 * File log.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Log
{
    /**
     * Add a log entry.
     * 
     * @param string $text
     * @throws \LogicException failed opening file
     */
    public static function add($text, $newLine = true) {
        /**
         * @todo check Yii::$app is not null & @test alias is defined.
         */
        
        $file = \Yii::getAlias('@tests') . '/testkit.log';
        $fh = fopen($file, 'a') or die("can't open file");
        if (false === $fh) {
            throw new \LogicException("Failed opening file " . $file);
        }
        if ($newLine) $text = "\n" . $text;
        fwrite($fh, $text);
        fclose($fh);
    }
}
