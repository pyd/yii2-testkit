<?php
namespace pyd\testkit\fixtures\db;

use yii\base\InvalidCallException;
use yii\base\InvalidParamException;

/**
 * Manage db fixture.
 * 
 * Basically this class handle load and unload of a collection tables.
 * @see \pyd\testkit\fixtures\db\TablesCollection
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Manager extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\fixtures\db\TablesCollection 
     */
    protected $collection;
    
    /**
     * Shortcut to get data used a as fixture for a db table.
     * 
     * ```php
     * $userData = $tablesManager->getTable('user')->getData();
     * // can be done with
     * $userData = $tablesManager->user;
     * ```
     *
     * @param string $name alias of a Table instance in the collection
     */
    public function __get($name)
    {
        if ($this->collection->hasKey($name)) {
            return $this->collection->get($name);
        } else {
            parent::__get($name);
        }
    }
    
    /**
     * Shortcut to get the ActiveRecord instance of a table row.
     *
     * ```php
     * $adminModel = $tablesManager->getTable('user')->getModel('admin', '\app\models\user\Admin');
     * // can be done with
     * $adminModel = $tablesManager->user('admin', '\app\models\user\Admin');
     * ```
     *
     * @param string $name alias of an item in the collection
     * @param array $params the first value must be the alias of a data row. A
     * second value (optional) can be the class name of the model to be
     * returned. If none is provided, the {@see \pyd\testkit\fixtures\db\Table::$modelClass}
     * will be used.
     * @return yii\db\ActiveRecord
     * @throws \yii\base\InvalidParamException unknown data row alias
     */
    public function __call($name, $params)
    {
        if ($this->collection->hasKey($name)) {

            if (empty($params)) {
                throw new InvalidCallException("Missing argument 1 for pyd\\testkit\\fixtures\\DbTable::getModel()"
                        . " called via " . __METHOD__ . ". You must provide the alias of a data row to populate the '$name' model.");
            }
            $dataRowAlias = $params[0];
            $modelClass = isset($params[1]) ? $params[1] : null;

            $model = $this->collection->get($name)->getModel($dataRowAlias, $modelClass);

            if (null === $model) {
                throw new InvalidParamException("Cannot create model for fixture"
                        . " '$name'. No data row matching alias '" . $dataRowAlias . "' was found.");
            }

            return $model;

        } else {
            parent::__call($name, $params);
        }
    }
    
    /**
     * @param strin|array|callback $type
     */
    public function setCollection($type)
    {
        $this->collection = \Yii::createObject($type);
        if (!$this->collection instanceof TablesCollection) {
            throw new InvalidParamException(__CLASS__ . '::$collection must be an instance of ' . TablesCollection::className());
        }
    }
    
    /**
     * Get a Table instance from the collection.
     *
     * @param string $alias alias of a Table instance in the collection
     * @return \pyd\testkit\fixtures\db\Table
     * @throws \yii\base\InvalidParamException
     */
    public function getTable($alias)
    {
        if ($this->collection->hasKey($alias)) {
            return $this->collection->get($alias);
        } else {
            throw new InvalidParamException("No Table instance found with alias '$alias'.");
        }
    }

    /**
     * Get all Table instances from the collection.
     *
     * @return array \pyd\testkit\fixtures\db\Table
     */
    public function getTables()
    {
        return $this->collection->getAll();
    }

    /**
     * Load all 'unloaded' tables of the collection.
     * 
     * @see \pyd\testkit\fixtures\db\Table::load()
     */
    public function load()
    {
        foreach ($this->collection->getAll() as $table) {
            $table->load();
        }
    }

    /**
     * Unload all 'loaded' tables of the collection.
     * 
     * @see \pyd\testkit\fixtures\db\Table::unload()
     */
    public function unload()
    {
        foreach (array_reverse($this->collection->getAll()) as $table) {
            $table->unload();
        }
    }
}
