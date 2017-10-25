<?php
namespace pyd\testkit\web\base;

/**
 * Create web element objects.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ElementCreator
{
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $driver;

    /**
     * The 'type' used by default to create elements.
     *
     * @var array
     * @see \Yii::createObject
     */
    protected $defaultType = ['class' => '\pyd\testkit\web\base\Element'];

    /**
     * @param \pyd\testkit\web\Driver $webDriver
     */
    public function __construct(\pyd\testkit\web\Driver $webDriver)
    {
        $this->driver = $webDriver;
    }

    /**
     * Create an element.
     *
     * @param string $elementID selenium internal ID of the web element
     * @param string|array|callable $type a definition of the object to be
     * created @see \Yii::createObject
     * @return \pyd\testkit\web\base\Element or subclass
     */
    public function create($elementID, $type = null)
    {
        if (null === $type) {
            $type = $this->defaultType;
        }
        return \Yii::createObject($type, [$elementID, $this->driver]);
    }
}
