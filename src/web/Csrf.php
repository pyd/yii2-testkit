<?php
namespace pyd\testkit\web;

/**
 * Utilities related to csrf token.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Csrf extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $driver;

    /**
     * @param \pyd\testkit\web\Driver $webDriver
     */
    public function __construct(Driver $webDriver)
    {
        $this->driver = $webDriver;
    }

    public function getToken()
    {
        $meta = $this->findMeta();
        if (null !== $meta) {
            return $meta->getAttribute('content');
        }
        $input = $this->findInput();
        if (null !== $input) {
            return $input->getAttribute('value');
        }
        return null;
    }

    public function findMeta()
    {
        try {
            return $this->driver->findElement(\WebDriverBy::cssSelector("meta[name='csrf-token']"));
        } catch (\NoSuchElementException $e) {
            return null;
        }
    }

    public function findInput()
    {
        $csrfParam = \Yii::$app->getRequest()->csrfParam;
        try {
            return $this->driver->findElement(\WebDriverBy::cssSelector("input[name='$csrfParam'"));
        } catch (\NoSuchElementException $e) {
            return null;
        }
    }
}
