<?php
namespace pyd\testkit\fixtures;

/**
 * Manager for fixture objects.
 *
 * This class ensures that Yii app fixture manager and Db fixture manager are
 * initialized and accessible.
 *
 * @see \pyd\testkit\fixtures\App
 * @see \pyd\testkit\fixtures\Db
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Manager extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\fixtures\App
     */
    protected $app;
    /**
     * @var \pyd\testkit\fixtures\Db
     */
    protected $db;

    public function init()
    {
        $properties = ['app', 'db'];
        foreach ($properties as $property) {
            if (null === $this->$property){
                throw new \yii\base\InvalidConfigException("Property " . get_class() . "::$property must be initialized.");
            }
        }
    }

    /**
     * @see $app
     * @return \pyd\testkit\fixtures\App
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @see \pyd\testkit\fixtures\Db
     * @return \pyd\testkit\fixtures\Db
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @see \pyd\testkit\fixtures\App
     * @param array $config
     */
    protected function setApp(array $config)
    {
        $this->app = \Yii::createObject($config);
    }

    /**
     * @see \pyd\testkit\fixtures\Db
     * @param array $config
     */
    protected function setDb(array $config)
    {
        $this->db = \Yii::createObject($config);
    }
}
