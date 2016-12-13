<?php
namespace pyd\testkit\fixtures\base;

use yii\base\InvalidConfigException;
use yii\base\InvalidCallException;
use pyd\testkit\fixtures\DbTable;

/**
 * Base class for the Db fixture manager.
 *
 * Create @see \pyd\testkit\fixtures\DbTable instances.
 * Load @see load() and unload @see unload() db tables represented by these
 * instances.
 *
 * @warning @see $dbTableInstances is initialized to an empty array. It is your
 * responsability to create DbTable instances @see createDbTableInstances()
 * before calling @see load() and @see unload().
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Db extends \yii\base\Object
{
    /**
     * @var array of @see pyd\testkit\fixtures\DbTable instances. Each key is
     * the FQ class name of the instance or an alias defined by the tester.
     */
    protected $dbTableInstances = [];

    /**
     * Get a @see $dbTableInstances by it's name.
     *
     * @param string $name DbTable class name or alias @see $_dbtableInstances
     * @return \pyd\testkit\fixtures\DbTable
     * @throws \yii\base\InvalidParamException
     */
    public function getDbTableInstance($name)
    {
        // if $name is a class name
        $name = ltrim($name, '\\');
        if (array_key_exists($name, $this->dbTableInstances)) {
            return $this->dbTableInstances[$name];
        }
        throw new \yii\base\InvalidParamException("Cannot find DbTable instance named '$name'.");
    }

    /**
     * Get DbTable instances.
     *
     * @see $dbTableInstances
     *
     * @return array of \pyd\testkit\fixtures\DbTable
     */
    public function getDbTableInstances()
    {
        return $this->dbTableInstances;
    }

    /**
     * Populate each db table of @see $dbTableInstances with fixture data if it's
     * status is not 'loaded'.
     *
     * @see pyd\testkit\fixtures\DbTable::load()
     * @see pyd\testkit\fixtures\DbTable::getIsLoaded()
     */
    public function load()
    {
        foreach ($this->dbTableInstances as $dbTable) {
            if (!$dbTable->getIsLoaded()) {
                $dbTable->load();
            }
        }
    }

    /**
     * Unload all loaded db tables.
     *
     * @param boolean $force db tables are unloaded without checking if they are
     * loaded
     * @see pyd\testkit\fixtures\DbTable::getIsLoaded()
     * @see pyd\testkit\fixtures\DbTable::unload()
     */
    public function unload($force = false)
    {
        foreach (array_reverse($this->dbTableInstances) as $dbTable) {
            if ($force || $dbTable->getIsLoaded()) {
                $dbTable->unload();
            }
        }
    }

    /**
     * Create @see \pyd\testkit\fixtures\Dbtable instance(s) using a configuration
     * array and store them in the @see $dbTableInstances property.
     *
     * What matters here are the dependencies @see pyd\testkit\fixtures\DbTable::$depends
     * of the instances.
     *
     * @var array $created temporary storage for DbTable instances before
     * initializing $dbTableInstances. Format is:
     * ['DbTable_FQ_class_name' => [alias' => 'alias_or_class_name', 'instance' => $dbTableInstance], ...]
     * @var array $dependsStack when a created DbTable instance has dependenc(y|ies),
     * it's FQ class name is stored here and so on and so forth for dependenc(y-ies)
     * of dependenc(y-ies). When a DbTable instance is added to the $created
     * array it's reference is removed from $dependsStack
     * @function $creator
     *
     * @param array $dbTableConfigs DbTable configurations
     * whose db tables are loaded
     */
    public function createDbTableInstances (array $dbTableConfigs)
    {
        $created = [];
        $dependsStack = [];

        // recursive: create DbTable
        $creator = function ($configs, $creatingDependencies) use (&$created, &$dependsStack, &$creator) {

            foreach ($configs as $key => $config) {

                // instance config must be valid
                if (is_string($config)) {
                    $config = ['class' => $config];
                } else if (!is_array($config)) {
                    throw new InvalidConfigException("DbTable config must be a string or an array.", 1);
                } else if (!array_key_exists('class', $config)) {
                    throw new InvalidConfigException("A DbTable config array must contain a 'class' key.", 2);
                }
                if (!class_exists($config['class'])) {
                    throw new InvalidConfigException("DbTable class " . $config['class'] . " does not exist.", 3);
                }


                // we'll need the class name without leading backslash
                $className = ltrim($config['class'], '\\');
                /** @var bool $keyIsAnAlias the DbTable config key is an alias
                 * vs key is an int and the instance will be stored with it's
                 * class name as key.
                 */
                $keyIsAnAlias = !is_int($key);

                // circular dependency detection e.g. DbTable instance A depends
                // on DbTable instance B which depends on DbTable instance C
                // which depends on DbTable instance A and the infinite loop begins
                // To avoid this we check if the currently processed DbTable class
                // name is already present in the $dependsStack
                if ($creatingDependencies && in_array($className, $dependsStack)) {
                    // current class name will appear in the exception message
                    array_push($dependsStack, $className);
                    $msg = "Circular dependency detected for the $className class. The dependencies stack contains:\n\t";
                    $msg .= implode("\n\tdepends on ", $dependsStack);
                    throw new InvalidConfigException($msg);
                }

                // The same DbTable class name can be processed more than once,
                // if not in a circular dependency (see above). In this case the
                // instance shouldn't be created again but it's config key, if
                // an alias, could overwrite the one used by the created instance.
                if (array_key_exists($className, $created)) {

                    if ($keyIsAnAlias) {
                        $createdInstanceKeyIsAnAlias = $created[$className]['alias'] === $className;
                        if (!$createdInstanceKeyIsAnAlias || !$creatingDependencies) {
                            $created[$className]['alias'] = $key;
                        }
                    }
                    continue;
                }

                // Ready to create the DbTable instance...
                $instance = \Yii::createObject($config);
                if (!$instance instanceof DbTable) {
                    throw new InvalidConfigException("Created object with class name $className is not an instance of DbTable.", 5);
                }

                // and eventually it's dependenc(y|ies).
                $instanceDependencies = $instance->depends;
                if (!empty($instanceDependencies)) {
                    // it's class name must be added to the dependencies stack
                    array_push($dependsStack, $className);
                    // it's dependenc(y|ies) must be processed before storing it as created
                    $creator($instanceDependencies, true);
                }

                // Dbtable instance is created and it's dependencies have been
                // processed. it can be added to the $created instances...
                $alias = $keyIsAnAlias ? $key : $className;
                $created[$className] = ['alias' => $alias, 'instance' => $instance];
                // and it's class name removed from the dependencies stack.
                if (in_array($className, $dependsStack)) {
                    $classNameKey = array_search($className, $dependsStack);
                    unset($dependsStack[$classNameKey]);
                }
            }
        };

        $creator($dbTableConfigs, false);

        foreach ($created as $className => $data) {
            $this->dbTableInstances[$data['alias']] = $data['instance'];
        }
    }
}
