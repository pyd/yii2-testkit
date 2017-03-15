<?php
namespace pyd\testkit\fixtures;

use pyd\testkit\Manager as Testkit;
use pyd\testkit\base\TestCase;
use yii\base\InvalidCallException;

/**
 * A manage for the @see \pyd\testkit\DbTable instances required by a test case.
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
     * @var boolean
     * @see \pyd\testkit\base\TestCase::$shareDbFixture
     */
    protected $testCaseShareDbFixture;
    /**
     * @var boolean the currently processed test case requires some db tables to
     * be loaded.
     */
    protected $testCaseRequireDb;

    /**
     * Get data from a @see $dbTableInstances instance.
     *
     * <code>
     * $userData = $fixtureDb->user;
     * // is a shortcut for
     * $userData = $this->dbFixture->getTable('user')->getData();
     * <code>
     *
     * @param string $name the alias of a @see $dbTableInstances instance
     * @throw UnknownPropertyException @see \yii\base\Object
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->dbTableInstances)) {
            return $this->dbTableInstances[$name]->getData();
        } else {
            parent::__get($name);
        }
    }

    /**
     * Get a model populated with data from a @see $dbTableInstances instance.
     *
     * <code>
     * $adminModel = $this->fixtureDb->user('admin', '\app\models\user\Admin');
     * // is a shortcut for
     * $adminModel = $this->fixtureDb->getDbTableInstance('user')->getModel('admin', '\app\models\user\Admin');
     * <code>
     *
     * @param string $name the alias of a @see $dbTableInstances instance
     * @param array $params the first value must be the alias of a data row. A
     * second value (optional) can be the class name of the returned model.
     * @return yii\db\ActiveRecord
     * @throws \yii\base\InvalidParamException $params[0] is not an existing data alias
     * @throws \yii\base\UnknownMethodException @see \yii\base\Object
     */
    public function __call($name, $params)
    {
        if (array_key_exists($name, $this->dbTableInstances)) {

            if (empty($params)) {
                throw new InvalidCallException("Missing argument 1 for pyd\\testkit\\fixtures\\DbTable::getModel()"
                        . " called via " . __METHOD__ . ". You must provide the alias of a data row to populate the '$name' model.");
            }
            $dataRowAlias = $params[0];
            $modelClass = isset($params[1]) ? $params[1] : null;

            $model = $this->dbTableInstances[$name]->getModel($dataRowAlias, $modelClass);

            if (null === $model) {
                throw new \yii\base\InvalidParamException("Cannot create model for fixture"
                        . " '$name'. No data row matching alias '" . $dataRowAlias . "' was found.");
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

            // force unload on DbTable instances when in dev mode
            if ($testCaseClassName::$devMode && $testCaseStart) {
                $this->unload(true);
            }
        }
    }

    /**
     * Handle 'setUp' event.
     *
     * If test method is executed in isolation, all tables must be unloaded.
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
            $this->dbTableInstances = [];
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
