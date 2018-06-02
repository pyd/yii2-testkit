<?php
namespace pyd\testkit\fixtures\db;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\InvalidCallException;

/**
 * Manage fixture for a table in db.
 * 
 * @see load() load db table with fixture data
 * @see unload() unload db table
 * @see getData() get fixture data for this table
 * @see getLoadedData() get the content of the table just after loading fixture data
 * @see getIsLoaded() get the loading state (loaded|unloaded) of the table. Note
 * that this state relies on usage of {@see load()} and {@see unload()} methods.
 * If you execute an SQL "DELETE FROM table_name;" request via DAO or ActiveRecord
 * the table will be empty but its state will remain 'loaded'.
 * @see forceLoadedState() to force table state (see below)
 * 
 * This class extends @see \yii\test\Fixture in order to be compatible with
 * {@see yii\console\controllers\FixtureController}.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Table extends \yii\test\Fixture
{
    /**
     * The table 'loading' status. True after {@see load()} and false after
     * {@see unload()}.
     * 
     * Be aware that modifying a table via DAO or activeRecord won't affect this
     * property.
     * 
     * This property has no usefulness when this instance is used by
     * {@see \yii\console\controllers\FixtureController} to load db via the console.
     * 
     * @var boolean
     */
    protected $isLoaded;
    
    /**
     * List of tables needed by this one (referential integrity).
     * 
     * These tables will be loaded before and unloaded after this one.
     * 
     * A table can be a class name or a config array used to create another
     * {@see \pyd\testkit\fixtures\db\Table} instance.
     * 
     * A table can be indexed by an alias to access its instance in
     * {@see \pyd\testkit\fixtures\db\TablesCollection}.
     * If none is provided, the FQ class name will be used as an alias.
     * ```php
     * $depends = [
     *      'users' => UsersFixture::className(),
     *      'countries' => [
     *          'class' => CountriesFixture::className(),
     *          'dataFile' => 'path/to/data/file'
     * ]
     * ```
     * 
     * @var array
     */
    public $depends = [];
    
    /**
     * The name of the db table managed by this instance.
     * 
     * If this property is not set, the {@see init()} method will try to
     * retrieve the table name from the {@see \yii\db\ActiveRecord::tableName()}
     * method if the {@see $modelClass} property is set.
     * 
     * @var string.
     */
    public $name;
    
    /**
     * The class name of a model for this table.
     * 
     * This will be used to get the table name if {@see $name} is not set.
     * This is the default class of the model returned by {@see getModel()}.
     * 
     * @var string
     */
    public $modelClass;
    
    /**
     * Path to the file returning fixture data for this table.
     * 
     * This property is used if the {@see $data} property is not set. It must be
     * the path to a php file returning an array.
     * 
     * If this property is not set, the {@see init()} method will try the default
     * '/path_to_this_fixture_class_file_dir/data/tableName.php' path:
     *      /.../fixtures
     *      /.../fixtures/data/users_table_name.php
     *      /.../fixtures/UsersFixtures.php
     *       
     * @var string
     */
    public $dataFile;
    
    /**
     * Raw fixture data.
     * 
     * If this property is not set, the {@see init()} method will try to retrieve
     * these data from a file {@see $dataFile}.
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
     * Backup of the table content done in the {@see load()} method.
     * 
     * This can be compared to the actual content of a table to know if it has
     * been updated.
     * 
     * @see load() where this property is set
     * 
     * @var array
     */
    protected $loadedData;
    
    /**
     * @var string|\yii\db\Connection if a string it must be an alias for the
     * connection component {@see init()}
     */
    protected $db = 'db';

    /**
     * Initialization.
     * 
     * Check that the {@see $db} has been set.
     * Resolve the {@see $name} property {@see $modelClass}.
     * Initialization of the {@see $tableSchema} property.
     * Initialization of the {@see $data} property {@see $dataFile}.
     * 
     * @throws InvalidConfigException:
     * - both @see $name and @see $modelClass are not defined;
     * - table {@see name} does not exist;
     * - {@see $dataFile} does not exist;
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
     * Load the db table with fixture data if it's not already loaded.
     * 
     * If db table is already loaded nothing happens.
     * 
     * @todo Is it necessary to reset the {@see $loadedData} property each time
     * this method is called? This makes sense only if {@see $data} changes
     * during the instance lifetime. Here we could check if {@see $loadedData} is
     * null and {@see setData()} could clear {@see $loadedData} when called.
     * 
     * 
     * @see $isLoaded
     */
    public function load()
    {
        if (true !== $this->isLoaded) {  
            
            foreach ($this->getDataToLoad() as $alias => $row) {
                $primaryKeys = $this->db->schema->insert($this->name, $row);
                $this->loadedData[$alias] = array_merge($row, $primaryKeys);
            }
            $this->isLoaded = true;
        }
    }

    /**
     * Unload db table - and reset it's sequence if any - if it's not already
     * unloaded.
     * 
     * If db table is already unloaded nothing happens.
     * 
     * @see $isLoaded
     */
    public function unload()
    {
        if (false !== $this->isLoaded) {  
            
            $this->db->createCommand()->delete($this->name)->execute();
            if (null !== $this->tableSchema->sequenceName) {
                $this->db->createCommand()->resetSequence($this->name, 1)->execute();
            }
            $this->isLoaded = false;
        }
    }
    
    /**
     * Force the value of the {@see $isLoaded} property.
     * 
     * This method can be used when the db table content does not matches the
     * {@see $isLoaded} property i.e. the table has been loaded|unloaded in
     * another process or via DAO or AvtiveRecord methods (not by using load or
     * unload methods).
     * 
     * @todo php7 set param type and remove exception
     * 
     * @param boolean $isLoaded
     */
    public function forceLoadedState ($isLoaded)
    {
        if (is_bool($isLoaded)) {
            $this->isLoaded = $isLoaded;
        } else {
            throw new InvalidParamException("Boolean param required.");
        }
    }

    /**
     * Get a model instance of a db table row identified by its data alias.
     *
     * @param string $name the data row alias
     * @param string $modelClass the class of the model instance to be returned,
     * if null the {@see $modelClass} is used
     * @return \yii\db\ActiveRecord the model instance
     * @throws InvalidParamException fixture data does not have such an alias
     * @throws InvalidConfigException the {@see $modelClass} property is not set
     * and no model class param is provided
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
     * Get all | one row(s) of the loaded data {@see $loadedData}.
     * 
     * @see getData() if you want raw fixture data
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
     * Get all | one row of the fixture raw data.
     * 
     * @see getLoadedData() to get the the db table content after fixture data load
     * 
     * @param string $rowAlias alias of the fixture data row
     * @return array data
     */
    public function getData($rowAlias = null)
    {
        if (null === $rowAlias) {
            return $this->data;
        }
        return $this->data[$rowAlias];
    }
    
    /**
     * @return boolean {@see $isLoaded}
     */
    public function getIsLoaded()
    {
        return $this->isLoaded;
    }
    
    /**
     * Get data to populate the table.
     *
     * This method can be used to process raw data before insertion, e.g. generate
     * a password hash from a clear password from fixture data...
     * 
     * @see $data is returned by default
     *
     * @return array
     */
    protected function getDataToLoad()
    {
        return $this->data;
    }
    
}
