<?php
namespace pyd\testkit\fixtures;

use pyd\testkit\Manager as Testkit;
use pyd\testkit\base\TestCase;

/**
 * Manage @see \pyd\testkit\DbTable instances required by a test case.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Db extends base\Db
{
    /**
     * @var pyd\testkit\Manager
     */
    protected $testkit;
    /**
     * @var bool if set to false, each test method will be executed with db
     * loaded with fresh data. If set to true db is loaded once with fresh
     * data
     */
    protected $testCaseShareDbFixture;
    /**
     * @var boolean the currently processed test case require some db tables to
     * be loaded with fixture data.
     */
    protected $testCaseRequireDb;

    /**
     * Get data from a @see $dbTables instance.
     *
     * ``php
     * $userTableData = $fixtureDb->user;
     * // is a shortcut for
     * $userTableData = $dbFixture->getTable('user')->getData();
     * ``
     *
     * @param string $name
     * @throw UnknownPropertyException see {@link \yii\base\Object}
     */
    public function __get($name)
    {
        if (array_key_exists($this->dbTableInstances[$name])) {
            return $this->dbTableInstances[$name]->getData();
        } else {
            parent::__get($name);
        }
    }

    /**
     * Get a model populated with data from a @see $dbTables instance.
     *
     * ``php
     * $adminModel = $fixtureDb->user('admin', '\app\models\user\Admin');
     * // is a shortcut for
     * $adminModel = $fixtureDb->getDbTableInstance('user')->getModel('admin', '\app\models\user\Admin');
     * // second param is the model class name. Use it if it's not defined in
     * // the DbTable class or if you want another
     * ``
     * @param string $name a fixture instance alias
     * @param array $params first item must be an alias of a data row. A second
     * item can contain a model class name.
     * @return yii\db\ActiveRecord
     * @throws \yii\base\InvalidParamException $params[0] is not an existing data alias
     * @throws \yii\base\UnknownMethodException see {@link \yii\base\Object}
     */
    public function __call($name, $params)
    {
        if (array_key_exists($name, $this->dbTableInstances)) {

            $dataRowAlias = $params[0];
            $modelClass = isset($params[1]) ? $params[1] : null;

            $model = $this->dbTableInstances[$name]->getModel($dataRowAlias, $modelClass);

            if (null === $model) {
                throw new \yii\base\InvalidParamException("Cannot create model for fixture"
                        . " named '$name'. There's no data row called '" . $dataRowAlias . "'.");
            }

            return $model;

        } else {
            parent::__call($name, $params);
        }
    }

    /**
     * Handle 'setUpBeforeClass' event.
     *
     * @param string $testCaseClassName class name of the currently executed
     * test case
     * @param boolean $testCaseStart the 'setUpBeforeClass' event occurs when
     * a new test case is executed vs it occurs before execution of a test method
     * in isolation
     * @param \pyd\testkit\Manager $testkit
     */
    public function onSetUpBeforeClass($testCaseClassName, $testCaseStart, Testkit $testkit)
    {
        $this->testkit = $testkit;
        $this->testCaseShareDbFixture = $testCaseClassName::$shareDbFixture;
        $dbTablesToLoad = $testCaseClassName::dbTablesToLoad();
        $this->testCaseRequireDb = !empty($dbTablesToLoad);

        if ($this->testCaseRequireDb) {
            $this->createDbTableInstances($dbTablesToLoad);
        }
    }

    /**
     * Handle 'setUp' event.
     *
     * If test method in isolation, all tables are unloaded.
     * Load db tables.
     *
     * @param type $testCaseInstance
     */
    public function onSetUp(TestCase $testCaseInstance)
    {
        if ($this->testCaseRequireDb) {
            $this->refreshInstancesLoadState();
            if ($testCaseInstance->isInIsolation()) {
                $this->unload();
            }
            $this->load();
        }
    }

    /**
     * Handle 'tearDown' event.
     *
     * Store loaded DbTable class names in case of an isolated test following.
     * Unload db if it's content must not be shared.
     */
    public function onTearDown(TestCase $testCaseInstance)
    {
        if ($this->testCaseRequireDb) {
            if (!$this->testCaseShareDbFixture || $testCaseInstance->isInIsolation()) {
                $this->unload();
            }
            $this->saveInstancesLoadState();
        }
    }

    /**
     * Handle 'tearDownAfterClass' event.
     *
     * Db tables are unloaded at the end of the test case.
     *
     * @param string $testCaseClassName class name of the currently executed
     * test case
     * @param boolean $testCaseEnd all test case methods were executed
     */
    public function onTearDownAfterClass($testCaseClassName, $testCaseEnd)
    {
        if ($this->testCaseRequireDb && $testCaseEnd) {
            $this->unload();
        }
    }

    public function isDbTableLoaded($className)
    {
        /** @todo implement isDbTableLoaded() */
    }

    /**
     * Store 'loaded' DbTable class names in shared memory.
     */
    protected function saveInstancesLoadState()
    {
        $loadedDbTableClassNames = [];
        foreach ($this->dbTableInstances as $dbTable) {
            if ($dbTable->getIsLoaded()) {
                $loadedDbTableClassNames[] = get_class($dbTable);
            }
        }
        $this->testkit->getSharedData()->setLoadedDbTables($loadedDbTableClassNames);
    }

    /**
     * Refresh @see $dbTableInstances $isLoaded property.
     */
    protected function refreshInstancesLoadState()
    {
        $loadedDbTableClassNames = $this->testkit->getSharedData()->getLoadedDbTables();
        foreach ($this->dbTableInstances as $dbTable) {
            $isloaded = in_array(get_class($dbTable), $loadedDbTableClassNames);
            $dbTable->refreshLoadState($isloaded);
        }
    }
}
