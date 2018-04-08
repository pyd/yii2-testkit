<?php
namespace pyd\testkit\web;

/**
 * Utilities related to csrf token.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Csrf extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\web\RemoteDriver
     */
    protected $driver;

    /**
     * @param \pyd\testkit\web\RemoteDriver $webDriver
     */
    public function __construct(RemoteDriver $webDriver)
    {
        $this->driver = $webDriver;
    }

    /**
     * Get CSRF token value.
     * 
     * @return string|null the csrf token value, null if value was not retrieved
     */
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

    /**
     * Find <meta> element that contains the CSRF token value.
     * 
     * @return \pyd\testkit\base\Element|null if meta element was not found
     */
    public function findMeta()
    {
        try {
            return $this->driver->findElement(\WebDriverBy::cssSelector("meta[name='csrf-token']"));
        } catch (\NoSuchElementException $e) {
            return null;
        }
    }

    /**
     * Find hidden <input> element that contains the CSRF token value.
     * 
     * @return \pyd\testkit\base\Element|null if meta element was not found
     */
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
