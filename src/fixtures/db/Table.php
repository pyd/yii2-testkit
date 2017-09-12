<?php
namespace pyd\testkit\fixtures;

use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\InvalidCallException;

/**
 * Manage db table data.
 *
 * It extends @see yii\test\Fixture to be compatible with
 * @see yii\console\controllers\FixtureController
 *
 * Instances of this class are created  by:
 * - @see pyd\testkit\fixtures\base\Db when in testing mode;
 * - @see yii\console\controllers\FixtureController to populate a db;
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class DbTable extends \yii\test\Fixture
{
    /**
     * @var boolean table was populated with fixture data
     */
    protected $isLoaded;
    /**
     * @var array if the table managed by this instance depends on other tables,
     * their DbTable class names or configs can be defined here
     * ```php
     * // example of what the 'app\tests\fixtures\UserFixture::$depends' property
     * // could contain:
     * protected $depends = [
     *      // user table depends on country table...
     *      'app\tests\fixtures\CountryFixture',
     *      // language table...
     *      'language' => ['class' => app\tests\fixtures\LanguageFixture::classname()],
     *      // company table...
     *      'company' => app\tests\fixtures\CountryFixture::className(),
     *      ...
     * ];
     * // Each value must be a DbTable class name or config array
     * // If a key - like 'language' - is defined, it can be use as an alias
     * // to access the associated Dbtable instance later:
     * $languageDbTable = self::getFixturesManager()->getFixtureDb()->getDbtableInstance('language');
     * // If the DbTable has no key, it's FQ class name must be used as an alias
     * $countryDbTable = self::getFixturesmanager()->getFixtureDb()->getDbTableInstance('app\tests\fixtures\CountryFixture);
     * // Note that DbTable aliases defined in the test case will overwrite the
     * // ones defined here. If the array returned by the
     * // app\tests\functional\user\ConnectionTest::dbTablesToLoad() method
     * // contains:
     * protected static function dbTableToLoad() '
     *      return [
     *          'user' => app\tests\fixtures\userFixture::className(),
     *          ...,
     *          'userLanguage' => app\tests\fixtures\LanguageFixture::className(),
     *          ...
     *      ];
     * }
     * // the 'userLanguage' alias will overwrite the 'language' one.
     * ```
     * @see pyd\testkit\fixtures\Db::createDbTableInstances() to understand
     * how DbTables instances are created
     */
    public $depends = [];
    /**
     * @var string name of the table. If not set, the name will be retrieved
     * from the model class @see init().
     */
    protected $tableName;
    /**
     * @var string name of the default model class used to create a model for
     * this instance.
     * @see getModel()
     */
    protected $modelClass;
    /**
     * @var array fixture data used to populate the db table.
     * @see init() for this property initialization
     * @see $dataFile if data are stored in a dedicated file
     */
    protected $data;
    /**
     * @var string path to the file returning fixture data to populate the db
     * table. If not set, a default path will be used
     * @see init()
     */
    protected $dataFile;
    /**
     * @var yii\db\Schema schema of the db table
     * @see init()
     */
    protected $tableSchema;
    /**
     * @var array content of the table after fixture data insertion. Each row
     * can be accessed using the fixture data aliases.
     * @see load()
     */
    protected $tableData;
    /**
     * @var string|\yii\db\Connection if a string it must be an alias for the
     * connection component @see init()
     */
    protected $db = 'db';

    /**
     * Initialization.
     *
     * @see $db property must ensure to a yii\base\db inbstance
     * @see $tableName or @see $modelClass must be initialized
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->db = \yii\di\Instance::ensure($this->db, '\yii\db\Connection');

        if (null === $this->tableName) {
            if (null !== $this->modelClass) {
                $modelClass = $this->modelClass;
                $this->tableName = $modelClass::tableName();
            } else {
                $className = get_class();
                throw new InvalidConfigException("Cannot resolve table name. You must initialize $className::\$tableName or $className::\$modelClass.", 20);
            }
        }

        $this->tableSchema = $this->db->getSchema()->getTableSchema($this->tableName);
        if (null === $this->tableSchema) {
            throw new InvalidConfigException("Table " . $this->tableName . " does not exist.");
        }

        if (null === $this->data) {
            if (null === $this->dataFile) {
                $rc = new \ReflectionClass($this);
                $this->dataFile = dirname($rc->getFileName()) . '/data/' . $this->tableName . '.php';
            }
            if (!is_file($this->dataFile)) {
                throw new InvalidConfigException("Data file '$this->dataFile' does not exist.");
            }
            $this->data = require($this->dataFile);
        }
    }

    /**
     * @see $isLoaded
     * @return boolean
     */
    public function getIsLoaded()
    {
        return $this->isLoaded;
    }

    /**
     * @todo php7 use boolean type to $isLoaded param
     * @param boolean $isLoaded
     */
    public function refreshLoadState($isLoaded)
    {
        $this->isLoaded = $isLoaded;
    }

    /**
     * Get data to be inserted into db table.
     *
     * This method can be used to process data before insert e.g. hash a clear
     * password.
     *
     * @return array
     */
    protected function getDataToLoad()
    {
        return $this->data;
    }

    /**
     * Populate table and store rows in @see $tableData.
     */
    public function load()
    {
        if ($this->isLoaded) {
            throw new InvalidCallException("Table '" . $this->tableName . "' is already loaded.");
        }
        foreach ($this->getDataToLoad() as $alias => $row) {
            $primaryKeys = $this->db->schema->insert($this->tableName, $row);
            $this->tableData[$alias] = array_merge($row, $primaryKeys);
        }
        $this->isLoaded = true;
    }

    /**
     * Remove data from table and reset it's sequence if any.
     */
    public function unload()
    {
        // if the isLoaded property is null, it means that this intance
        // was created by  the yii\console\controllers\FixtureController::load
        // method which by default unload table before to load it. In this case,
        // unload an 'unloaded' table should not throw an exception.
        if (null!== $this->isLoaded && !$this->isLoaded) {
            throw new InvalidCallException("Table '" . $this->tableName . "' is already unloaded.");
        }
        $this->tableData = [];
        $this->db->createCommand()->delete($this->tableName)->execute();
        if (null !== $this->tableSchema->sequenceName) {
            $this->db->createCommand()->resetSequence($this->tableName, 1)->execute();
        }
        $this->isLoaded = false;
    }

    /**
     * Get an ActiveRecord instance of the table row matching the $name argument.
     *
     * @param string $name the data row alias
     * @param string $modelClass the model class name
     * @return null|\yii\db\ActiveRecord the AR model, or null if the model cannot be found in the database
     * @throws \yii\base\InvalidConfigException if [[modelClass]] is not set.
     */
    public function getModel($name, $modelClass = null)
    {
        if (!isset($this->tableData[$name])) {
            return null;
        }

        if (null === $modelClass) $modelClass = $this->modelClass;

        if (null === $modelClass) {
            throw new InvalidConfigException('The "modelClass" property must be set.');
        }
        $row = $this->tableData[$name];
        /* @var $model \yii\db\ActiveRecord */
        $model = new $modelClass;
        $keys = [];
        foreach ($model->primaryKey() as $key) {
            $keys[$key] = isset($row[$key]) ? $row[$key] : null;
        }
        return $modelClass::findOne($keys);
    }

    /**
     * @return array
     * @see $tableData
     */
    public function getTableData()
    {
        if ($this->isLoaded) {
            return $this->tableData;
        } else {
            throw new InvalidCallException("Cannot return table data because it's not loaded.");
        }

    }

    /**
     * @return string
     * @see $dataFile
     */
    public function getDataFile()
    {
        return $this->dataFile;
    }

    /**
     *
     * @param string $alias a key of the initial data array
     * @return array data
     */
    public function getData($alias = null)
    {
        if (null === $alias) {
            return $this->data;
        }
        return $this->data[$alias];
    }
}
