<?php
namespace tests\functional;

use yii\helpers\BaseConsole;

/**
 * Base web test case class for yii2 basic app.
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class WebTestCase extends \pyd\testkit\functional\WebTestCase {

    public function showTrainingMessage($msg, $exception = null)
    {
        $exceptionMsg = (null === $exception) ? '' : $exception->getMessage();
        $this->fail($exceptionMsg . BaseConsole::ansiFormat($msg, [BaseConsole::FG_YELLOW, BaseConsole::BG_BLACK]));
    }

    public function showMessage($msg)
    {
        echo BaseConsole::ansiFormat($msg, [BaseConsole::FG_GREEN, BaseConsole::BG_BLACK]);
    }
}
