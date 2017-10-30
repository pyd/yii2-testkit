<?php
namespace pyd\testkit\fixtures\db;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\InvalidCallException;

/**
 * Manage the content of a db table.
 *
 * This class extends @see \yii\test\Fixture in order to be compatible with
 * @see yii\console\controllers\FixtureController
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Table extends \yii\test\Fixture
{
    /**
     * @var boolean table was populated with fixture data
     */
    protected $isLoaded;
    /**
     * Tables that must be loaded before this one (referential integrity).
     * 
     * A table can be a class name or a config array used to create a
     * @see \pyd\testkit\fixtures\db\Table instance
     * 
     * A table can be indexed by an alias which can be used later to access its
     * instancein a collection. If no alias is provided, the class name is used
     * to identify the Table instance.
     * 
     * <code>
     * $depends = [
     *      app\models\User::className(),
     *      'country' => [
     *          'class' => app\models\Country::className(),
     *          'dataFile' => 'path_to_data_file'
     * ]
     * </code>
     * 
     * @var array
     */
    public $depends = [];
    /**
     * The name of the table.
     * 
     * @see init if not set, the @see $modelClass::tableName() is used
     * 
     * @var string.
     */
    public $name;
    /**
     * The class name of a model for this table.
     * 
     * This will be used to get the table name if @see $name is not set.
     * This is the default class of the model returned by @see getModel().
     * 
     * @var string
     */
    public $modelClass;
    /**
     * Path to the file returning fixture data for this table.
     * 
     * If none, the @see $data property should be set.
     * 
     * @see init() needed if the @see $data property is not set
     * 
     * @var string
     */
    public $dataFile;
    /**
     * Raw fixture data.
     * 
     * @see init() if not set, data will be retrieved from a file
     * 
     * @var array
     */
    protected $data;
    /**
     * Schema of the table.
     * 
     * @see init() for initialization
     * 
     * @var yii\db\Schema
     */
    protected $tableSchema;
    /**
     * Content of the table after loading.
     * 
     * @see load() where this property is set
     * 
     * @var array
     */
    protected $loadedData;
    /**
     * @var string|\yii\db\Connection if a string it must be an alias for the
     * connection component @see init()
     */
    protected $db = 'db';

    /**
     * Initialization.
     * 
     * The @see $db property is required.
     * 
     * If the @see $name property is not defined it will be initialized
     * using @see $modelClass:tableName(). The table must exist.
     * 
     * The @see $tableSchema property is initialized.
     * 
     * If the @see $data property is not initialized, its content will be retrieved
     * from a file. If the @see $dataFile property is not defined, a generic
     * path is used: path_to_this_file_parent_directory/data/table_name.php
     *
     * @throws InvalidConfigException:
     * - both @see $name and @see $modelClass are not defined;
     * - table does not exist;
     * - data file does not exist;
     */
    public function init()
    {
        $this->db = \yii\di\Instance::ensure($this->db, '\yii\db\Connection');

        if (null === $this->name) {
            if (null !== $this->modelClass) {
                $modelClass = $this->modelClass;
                $this->name = $modelClass::tableName();
            } else {
                $className = get_class();
                throw new InvalidConfigException("Cannot resolve table name. You must initialize $className::\$name or $className::\$modelClass.", 20);
            }
        }

        $this->tableSchema = $this->db->getSchema()->getTableSchema($this->name);
        if (null === $this->tableSchema) {
            throw new InvalidConfigException("Table " . $this->name . " does not exist.");
        }

        if (null === $this->data) {
            if (null === $this->dataFile) {
                $rc = new \ReflectionClass($this);
                $this->dataFile = dirname($rc->getFileName()) . '/data/' . $this->name . '.php';
            }
            if (!is_file($this->dataFile)) {
                throw new InvalidConfigException("Data file '$this->dataFile' does not exist.");
            }
            $this->data = require($this->dataFile);
        }
    }

    /**
     * @return boolean
     */
    public function getIsLoaded()
    {
        return $this->isLoaded;
    }

    /**
     * Force the table load state.
     * 
     * @param boolean $isLoaded
     */
    public function forceLoadState($isLoaded)
    {
        $this->isLoaded = $isLoaded;
    }

    /**
     * Get data to populate the table.
     *
     * This method can be used to process raw data before insertion.
     * @see $data is returned by default
     *
     * @return array
     */
    protected function getDataToLoad()
    {
        return $this->data;
    }

    /**
     * Load data into the table.
     * 
     * Data to load are provided by the @see getDataToLoad() method.
     * 
     * All inserted row are stored in the @see $loadedData property.
     */
    public function load()
    {
echo "\nLoading " . $this->name;
        if ($this->isLoaded) {
            throw new InvalidCallException("Table '" . $this->name . "' is already loaded.");
        }
        foreach ($this->getDataToLoad() as $alias => $row) {
            $primaryKeys = $this->db->schema->insert($this->name, $row);
            $this->loadedData[$alias] = array_merge($row, $primaryKeys);
        }
        $this->isLoaded = true;
    }

    /**
     * Remove data from table and reset it's sequence if any.
     */
    public function unload()
    {
echo "\nUnloading " . $this->name;
        // if the isLoaded property is null, it means that this intance
        // was created by  the yii\console\controllers\FixtureController::load
        // method which by default unload table before to load it. In this case,
        // unload an 'unloaded' table should not throw an exception.
        if (null!== $this->isLoaded && !$this->isLoaded) {
            throw new InvalidCallException("Table '" . $this->name . "' is already unloaded.");
        }
        $this->loadedData = [];
        $this->db->createCommand()->delete($this->name)->execute();
        if (null !== $this->tableSchema->sequenceName) {
            $this->db->createCommand()->resetSequence($this->name, 1)->execute();
        }
        $this->isLoaded = false;
    }

    /**
     * Get a model instance of a row identified by its data alias.
     *
     * @param string $name the data row alias
     * @param string $modelClass the model class name if null the @see $modelClass
     * is used
     * @return \yii\db\ActiveRecord the model
     * @throws InvalidParamException 
     * @throws InvalidConfigException
     */
    public function getModel($dataRowAlias, $modelClass = null)
    {
        if (!isset($this->loadedData[$dataRowAlias])) {
            throw new InvalidParamException("Unknown data row alias '$dataRowAlias' for table " . $this->name . '.');
        }

        $modelClass = (null === $modelClass) ? $this->modelClass : $modelClass;
        if (null === $modelClass) {
            throw new InvalidConfigException('The "modelClass" property must be set.');
        }

        $row = $this->loadedData[$dataRowAlias];
        /* @var $model \yii\db\ActiveRecord */
        $model = new $modelClass;
        $keys = [];
        foreach ($model->primaryKey() as $key) {
            $keys[$key] = isset($row[$key]) ? $row[$key] : null;
        }
        return $modelClass::findOne($keys);
    }

    /**
     * Get all|one row of the table data as it was just after insertion.
     * 
     * @see getData() to get raw data
     * 
     * @return array
     */
    public function getLoadedData($dataRowAlias = null)
    {
        if ($this->isLoaded) {
            if (null !== $dataRowAlias) {
                return $this->loadedData[$dataRowAlias];
            } else {
                return $this->loadedData;
            }
        } else {
            throw new InvalidCallException("Cannot return table data because it's not loaded.");
        }

    }

    /**
     * Set fixture raw data.
     * 
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * Get all|one row of the fixture raw data.
     * 
     * @see getLoadedData() to get the data as it was in the table just after
     * insertion.
     * 
     * @param string $dataRowAlias key of the data row
     * @return array data
     */
    public function getData($dataRowAlias = null)
    {
        if (null === $dataRowAlias) {
            return $this->data;
        }
        return $this->data[$dataRowAlias];
    }
}
