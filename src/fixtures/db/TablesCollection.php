<?php
namespace pyd\testkit\fixtures\db;

use Yii;
use pyd\testkit\fixtures\db\Table;
use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

/**
 * Manage a collection of {@see pyd\testkit\fixtures\db\Table} instances.
 * 
 * The main usage of a Table instance is to load and unload a db table with
 * fixture data. The role of this collection is to provide an ordered list of
 * these instances to safely load and unload the db tables required during tests.
 * 
 * Rules are:
 * - there can be only one instance of a Table in the collection (don't want to
 *   load a table twice);
 * - if a TableA has dependencies {@see pyd\testkit\fixtures\db\Table::$depends}
 *   their instances will be added before TableA in the collection so their db
 *   table will be loaded before and unloaded after TableA table;
 * 
 * Instance alias.
 * An instance is identified by an alias in the collection. This alias can be
 * set when a Table is added, e.g. with {@see add} or when it appears as a
 * dependency.
 * ```php
 * class UsersFixture extends Table {
 *      public $depends = ['countries' => CountriesFixture::className()]
 * }
 * $collection->add(UsersFixture::className(), 'users');
 * $tables = $collection->getAll();
 * // returns ['countries' => $coutriesFixtureInstance, 'users' => $usersFixtureInstance]
 * ```
 * Aliases rules:
 * - if no alias is provided for a Table, its FQ class name is used ;
 * - an alias defined via {@see add} always takes precedence on an alias defined
 *   in {@see pyd\testkit\fixtures\db\Table::$depends};
 * - if aliases for a Table have the same 'origin', i.e. add() or $depends, the
 *   last defined is used;
 * 
 * @see \pyd\testkit\fixtures\db\TablesCollectionCircularDependencyException thrown
 * when a TableA is added that depends on TableB that depends on TableC that depends
 * on TableA to avoid an infinite loop of dependencies processing
 * 
 * @author Pierre-Yves DELETTRE 
 */
class TablesCollection extends \yii\base\Object
{
    /**
     * @see pyd\testkit\fixtures\db\Table
     * @var array Table instances indexed by class name or alias
     */
    protected $tables = [];
    
    /**
     * @var array class names of the instances in the collection
     */
    protected $tableClassNames = [];
    
    /**
     * Get all {@see $tables} instances.
     * 
     * @return array
     */
    public function getAll()
    {
        return $this->tables;
    }
    
    /**
     * Create a {@see pyd\testkit\fixtures\db\Table} instance and add it to the
     * collection.
     * 
     * If the Table has dependencies, they will be added before it in the
     * collection.
     * 
     * @param string|array $type class name or config array to create the Table
     * instance
     * @param string $alias used as a key to identify|access the instance in the
     * collection. If null, the FQ class name of the Table is used as key.
     */
    public function add($type, $alias = null)
    {   
        // detect circular dependency if a class name is already present
        $dependencieStack = [];
        
        // additional params (use) value is intialized when the Closure is build
        // in order for this params to be updated, they have to be passed as reference
        $create = function($type, $alias, $isDependency) use (&$create, &$dependencieStack){
        
            // must ensure it is an instance of Table
            $instance = $this->createTableInstanceOrFail($type);
        
            $className = get_class($instance);
            
            // an instance of the same class is already present in the collection
            // may be its current alias should be replaced by the new alias?
            if (in_array($className, $this->tableClassNames)) {
                
                if (null !== $alias) {
                    $currentAlias = array_search($instance, $this->tables);
                    // scenarii:
                    // - current alias is a class name: it must be replaced by the new one;
                    // - current alias is not a class name: it must be replaced by
                    // the new one if the latter was defined in a test case (not in
                    // a Table::$depends property.
                    if ($currentAlias === $className || !$isDependency) {
                        $this->updateTableKey($currentAlias, $alias);
                    }
                    
                }
                return;
            }
            
            // circular dependencies detection
            if (in_array($className, $dependencieStack)) {
                throw new CircularDependencyException($dependencieStack);
            }
            $dependencieStack[] = $className;
            
            // handle dependencies before adding instance to the collection
            if (!empty($instance->depends)) {
                foreach ($instance->depends as $key => $dependency) {
                    // integer key means no alias
                    if (!is_string($key)) $key = null;
                    $create($dependency, $key, true);
                }
            }
            
            // add instance
            $key = (null === $alias) ? $className : $alias;
            $this->tables[$key] = $instance;
            $this->tableClassNames[] = $className;
            
        };
        
        $create($type, $alias, false);
    }
    
    /**
     * Modify the key of a Table instance in the collection.
     * 
     * @param string $currentKey the key to be modified
     * @param string $newKey
     */
    public function updateTableKey($currentKey, $newKey)
    {
        $keys = array_keys($this->tables);
        $currentKeyIndex = array_search($currentKey, $keys);
        $keys[$currentKeyIndex] = $newKey;
        $this->tables = array_combine($keys, array_values($this->tables));
    }
    
    /**
     * Clear the collection.
     */
    public function clear()
    {
        $this->tables = [];
        $this->tableClassNames = [];
    }
    
    /**
     * Check if there is an instance with such a key in the collection.
     * 
     * @param string $key alias or FQ class name of a Table
     * @return boolean
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->tables);
    }
    
    /**
     * Get a Table instance from the collection by its key/alias.
     * 
     * @param string $key key of the instance
     * @return \pyd\testkit\fixtures\db\Table
     */
    public function get($key)
    {
        // if an instance is indexed by its class name, it won't have a leading slash
        $key = ltrim($key, '\\');
        return $this->tables[$key];
    }
    
    /**
     * Create an instance based on the $type param and ensure it is an instance
     * of {@see \pyd\testkit\fixtures\db\Table}.
     * 
     * @param string|array $type class name or config array
     * @return \pyd\testkit\fixtures\db\Table
     * @throws InvalidParamException the created object is not an instance of Table
     */
    public function createTableInstanceOrFail($type)
    {
        $instance = Yii::createObject($type);
        if ($instance instanceof Table) {
            return $instance;
        } else {
            throw new InvalidParamException("Object of class " . get_class($instance)
                    . " is not an instance of \pyd\testkit\fixtures\db\Table.");
        }
    }
    
    /**
     * Create several instances of {@see \pyd\testkit\fixtures\db\Table} and add
     * them to the collection.
     * 
     * Unlike the {@see setTables} method, the collection is not cleared before
     * adding the new instances.
     * 
     * @param array $tables class names and/or config arrays to create the
     * {@see \pyd\testkit\fixtures\db\Table} instances.
     */
    public function addBatch (array $tables)
    {
        foreach ($tables as $key => $table) {
            if (is_int($key)) $key = null;
            $this->add($table, $key);
        }
    }
        
    /**
     * Set Table instances of the collection from an array of configs.
     * 
     * Be aware that unlike the {@see addBatch} method, this method will first
     * clear the collection of all its instances.
     *
     * @param array $tables class names and/or config arrays to create the
     * {@see \pyd\testkit\fixtures\db\Table} instances.
     */
    public function setTables (array $tables)
    {
        $this->clear();
        
        // store dependencies class names to detect circular dependency
        $dependsStack = [];
        // store instances [className => instance, ...]
        $classInstance = [];
        // store instance keys [className => instanceKey, ...]
        $classKey = [];

        // recursive 
        $creator = function ($configs, $creatingDependencies) use (&$classInstance, &$classKey, &$dependsStack, &$creator) {

            foreach ($configs as $key => $config) {

                $instance = $this->createTableInstanceOrFail($config);
                $className = get_class($instance);
                
                
                
                // is there an alias to index the instance in the collection
                $keyIsAlias = !is_int($key);

                // circular dependency detection e.g. Table instance A depends
                // on Table instance B which depends on Table instance C
                // which depends on Table instance A and the infinite loop begins
                // To avoid this we check if the currently processed Table class
                // name is already present in the $dependsStack
                if ($creatingDependencies && in_array($className, $dependsStack)) {
                    throw new CircularDependencyException($dependsStack);
                }

                // The same TableFixture class can appear more than once
                // without leading to a circular dependency exception.
                // It should appear only once in TestCase::dbTableToLoad() but
                // may be mentioned many times as a dependency.
                // If the Table instance is already created, we just have to choose
                // the best key to index it:
                // - an alias (defined by tester) is better than a class name;
                // - between 2 aliases, the last one will be preferred if it was
                // defined in TestCase::dbTableToLoad() (not as dependency);
                
                if (array_key_exists($className, $classInstance)) {
                    if ($keyIsAlias) {
                        $existingKeyIsAlias = $classKey[$className] !== $className;
                        if (!$existingKeyIsAlias || !$creatingDependencies) {
                            $classKey[$className] = $key;
                        }
                    }
                    continue;
                }

                // and eventually its dependenc(y|ies).
                $instanceDependencies = $instance->depends;
                if (!empty($instanceDependencies)) {
                    // its class name must be added to the dependencies stack
                    array_push($dependsStack, $className);
                    // its dependenc(y|ies) must be processed before storing it as created
                    $creator($instanceDependencies, true);
                }

                // Table instance is created and its dependencies have been
                // processed. it can be added to the $created instances...

                $alias = $keyIsAlias ? $key : $className;
                $classInstance[$className] = $instance;
                $classKey[$className] = $alias;
                // and its class name removed from the dependencies stack.
                if (in_array($className, $dependsStack)) {
                    $classNameKey = array_search($className, $dependsStack);
                    unset($dependsStack[$classNameKey]);
                }
            }
        };

        $creator($tables, false);

        foreach ($classInstance as $className => $instance) {
            $this->tables[$classKey[$className]] = $instance;
            $this->tableClassNames[] = $className;
        }
    }
}
