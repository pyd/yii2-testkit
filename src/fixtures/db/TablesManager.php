<?php
namespace pyd\testkit\fixtures\db;

use yii\base\InvalidCallException;
use yii\base\InvalidParamException;

/**
 * Manage a {@see $collection} of {@see \pyd\testkit\fixtures\db\Table} instances.
 * 
 * This class provides methods to load|unload all db tables of the collection
 * and to access to the {@see \pyd\testkit\fixtures\db\Table} instances.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class TablesManager extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\fixtures\db\TablesCollection 
     */
    protected $collection;
    
    /**
     * Shortcut to get fixture data of a db table which has an instance in the
     * collection.
     * 
     * Table instance must be indexed by an alias in the collection.
     *
     * <code>
     * // 'user' is the Table instance alias in the collection
     * $userData = $tablesManager->user;
     * // is a shortcut for
     * $userData = $tablesManager->getTable('user')->getData();
     * <code>
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
     * Get the ActiveRecord instance of a table row.
     *
     * <code>
     * $adminModel = $tablesManager->user('admin', '\app\models\user\Admin');
     * // is a shortcut for
     * $adminModel = $tablesManager->getTable('user')->getModel('admin', '\app\models\user\Admin');
     * <code>
     *
     * @param string $name a key (alias or class name) of an item in the collection
     * @param array $params the first value must be the alias of a data row. A
     * second value (optional) can be the class name of the returned model.
     * @return yii\db\ActiveRecord
     * @throws \yii\base\InvalidParamException $params[0] is not an existing data alias
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
        /**
         * @todo check created instance class
         */
        $this->collection = \Yii::createObject($type);
    }
    
    /**
     * Get a Table instance from the collection by its key.
     *
     * @param string $key Table class name or alias
     * @return \pyd\testkit\fixtures\db\Table
     * @throws \yii\base\InvalidParamException
     */
    public function getTable($key)
    {
        if ($this->collection->hasKey($key)) {
            return $this->collection->get($key);
        } else {
            throw new InvalidParamException("No Table instance indexed with '$key' was found in the collection.");
        }
    }

    /**
     * Get all Table instances.
     *
     * @return array \pyd\testkit\fixtures\db\Table
     */
    public function getTables()
    {
        return $this->collection->getAll();
    }

    /**
     * Load all unloaded tables of the collection.
     * 
     * Call the {@see \pyd\testkit\fixtures\db\Table::load} method of all the
     * instances of the {@see $collection} which are not already loaded
     * {@see \pyd\testkit\fixtures\db\Table::$isLoaded}.
     */
    public function load()
    {
        foreach ($this->collection->getAll() as $table) {
            if (!$table->getIsLoaded()) {
                $table->load();
            }
        }
    }

    /**
     * Unload all tables of the collection.
     * 
     * Call the {@see \pyd\testkit\fixtures\db\Table::unload} method of all the
     * instances of the {@see $collection} which are already loaded - or not if
     * the $force param is set to true - {@see \pyd\testkit\fixtures\db\Table::$isLoaded}.
     *
     * @param boolean $force tables will be unloaded even if their status is
     * 'not loaded'.
     */
    public function unload($force = false)
    {
        foreach (array_reverse($this->collection->getAll()) as $table) {
            if ($force || $table->getIsLoaded()) {
                $table->unload($force);
            }
        }
    }
}
