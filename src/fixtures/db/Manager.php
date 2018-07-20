<?php
namespace pyd\testkit\fixtures\db;

use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use pyd\testkit\fixtures\db\TablesCollection;

/**
 * Manage db fixture.
 * 
 * Basically this class provides methods to load and unload some tables in db.
 * It also give access to objects representing those db tables.
 * 
 * @see \pyd\testkit\fixtures\db\TablesCollection
 * @see \pyd\testkit\fixtures\db\Table
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Manager extends \yii\base\BaseObject
{
    /**
     * @var \pyd\testkit\fixtures\db\TablesCollection 
     */
    protected $collection;
    
    /**
     * Shortcut to get raw fixture data for a db table of the {@see $collection}.
     * 
     * ```php
     * // 'users' is the alias of an item in the collection
     * $usersTableFixture = $dbFixtureManager->users;
     * // is a shortcut for
     * $usersTableFixture = $dbFixtureManager->getTable('users')->getData();
     * ```
     *
     * @param string $name alias of a \pyd\testkit\fixtures\db\Table instance
     * in the {@see $collection}
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
     * Shortcut to get the ActiveRecord instance of a db table row.
     *
     * ```php
     * // 'users' is the alias of an item in the collection
     * // 'admin' is the alias of a fixture data row for the 'users' table
     * $adminModel = $dbFixtureManager->users('admin', '\app\models\users\Admin');
     * // is a shortcut for
     * $adminModel = $dbFixtureManager->getTable('users')->getModel('admin', '\app\models\user\Admin');
     * ```
     * 
     * @param string $name alias of an item in the collection
     * @param array $params the first value must be the alias of a data row. A
     * second value (optional) can be the class name of the model to be
     * returned. If none is provided, the default
     * {@see \pyd\testkit\fixtures\db\Table::$modelClass} will be used.
     * @return yii\db\ActiveRecord
     * @throws InvalidCallException $params can not be empty. It must at least
     * contain a data row alias to populate the model to be returned
     * @throws InvalidParamException the data row alias - first value of $params -
     * does not exist 
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
     * @throws InvalidParamException $type is neither an instance of
     * {@see \pyd\testkit\fixtures\db\TablesCollection} nor a configuration to
     * create an object of this class
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
     * @see \pyd\testkit\fixtures\db\Table::$isLoaded
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
     * @see \pyd\testkit\fixtures\db\Table::$isLoaded
     */
    public function unload()
    {
        foreach (array_reverse($this->collection->getAll()) as $table) {
            $table->unload();
        }
    }
}
