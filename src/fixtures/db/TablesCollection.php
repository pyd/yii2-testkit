<?php
namespace pyd\testkit\fixtures\db;

use Yii;
use pyd\testkit\fixtures\db\Table;
use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;

/**
 * A collection of Table {@see pyd\testkit\fixtures\db\Table} instances used to
 * load|unload db table's fixture.
 * 
 * The collection won't contain more than one instance of a Table class.
 * ```php
 * $collection->add(CountriesFixture::className());
 * $collection->add(UsersFixture::className());     // UsersFixture depends on CountriesFixture
 * // collection contains only one instance of CountriesFixture
 * ```
 * Dependencies {@see pyd\testkit\fixtures\db\Table::$depends} instances are added
 * to the collection before their parent.
 * ```php
 * $collection->add(UsersFixture::className());     // UsersFixture depends on CountriesFixture
 * \\ collection first item is an instance of CountriesFixture and the second
 * \\ an instance of UsersFixture
 * ```
 * 
 * Instance's keys.
 * ```php
 * // no alias defined => key is Table class name
 * $collection->add(CountriesFixture::className());
 * $countries = $collection->get(CountriesFixture::className());
 * // alias defined in dependencies can overwrite class named key but not previous alias
 * $collection->add(['class' = UsersFixture::className(), 'depends' => ['countriesAlias1' => CountriesFixture::className()]);
 * $countries = $collection->get('countriesAlias1');
 * // alias not defined in dependencies will always overwrite a previous alias
 * $collection->add(CountriesFixture::className(), 'countriesAlias2');
 * $countries = $collection->get('countriesAlias2');
 * ```
 * 
 * This class handles 'circular dependency' detection when adding a Table e.i.
 * adding TableA that depends on TableB that depends on TableA.
 * 
 * @author Pierre-Yves DELETTRE 
 */
class TablesCollection extends \yii\base\Object
{
    /**
     * @see pyd\testkit\fixtures\db\Table
     * @var array instances of Table indexed by their class name or an alias
     */
    protected $tables = [];
    /**
     * @var array class names of the instances in the collection
     */
    protected $tableClassNames = [];
    
    /**
     * @return array all instances of {@see \pyd\testkit\fixtures\db\Table} from
     * the collection
     */
    public function getAll()
    {
        return $this->tables;
    }
    
    /**
     * Create a {@see pyd\testkit\fixtures\db\Table} instance and add it to the
     * collection.
     * 
     * @param string|array $type class name or config array to create the Table
     * instance
     * @param string $alias used as a key to identify|access the instance in the
     * collection. If null, the instance class name is used as key.
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
                        $this->modifyTableKey($currentAlias, $alias);
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
    public function modifyTableKey($currentKey, $newKey)
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
     * @param string $key alias or class name
     * @return boolean
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->tables);
    }
    
    /**
     * Get a table instance from the collection.
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
     * Create some instances of {\pyd\testkit\fixtures\db\Table} and add them to
     * the collection.
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

/**
 * This exception is thrown when an infinite loop is detected when adding Table
 * instances to the collection.
 */
class CircularDependencyException extends \Exception{
    
    /**
     * @param array $dependencyStack list of Table class names involved in the
     * circular dependency
     */
    public function __construct(array $dependencyStack) {
        parent::__construct($this->formatMessage($dependencyStack));
    }

    /**
     * Format exception message.
     * 
     * @param array $dependencyStack class names involved in the circular
     * dependency
     * @return string
     */
    protected function formatMessage(array $dependencyStack) {
        $loopOrigin = array_shift($dependencyStack);
        $msg = "\nCircular dependency detected for " . $loopOrigin . "class:";
        foreach ($dependencyStack as $classname) {
            $msg .= "\n\twhich depends on $classname";
        }
        $msg .= "\n\twhich depends on $loopOrigin <- back to origin";
        return $msg;
    }
}
