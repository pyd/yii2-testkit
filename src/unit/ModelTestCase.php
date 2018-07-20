<?php
namespace pyd\testkit\unit;

use yii\base\Model;
use yii\di\Instance;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\base\UnknownPropertyException;
use pyd\testkit\AssertionMessage;

/**
 * Base class to 'unit test' Models.
 * 
 * Usage: create a test case class extending ModelTestCase.
 * @see modelReference() to define model to test at the test case level
 * @see setModel() to define model to test at the test method level
 *
 * <code>
 * class UsersCreateTest extends ModelTestCase
 * {
 *      public function modelReference()
 *      {
 *          return UsersCreate::className();
 *      }
 *
 *      public function checkAttributes()
 *      {
 *          // default scenario
 *          $this->assertSafeAttributes(['username', 'password', 'mail']);
 *          $this->assertRequiredAttributes(['username', 'password', 'mail']);
 *          
 *          $this->getModel()->setScenario(UsersCreate::SCENARIO_ADMIN);
 *          $this->assertSafeAttributes(['username', 'password', 'mail', 'admin_level']);
 *          $this->assertRequiredAttributes(['username', 'password', 'mail', 'admin_level']);
 *      }
 * 
 *      public function checkValidation()
 *      {
 *          $validationData = [...];
 *          $this->assertValidationDataMatchesValidationRules($validationData);
 *          
 *      }
 * 
 *      public function checkMailMustBeUnique()
 *      {
 *          // set a custom model for this test method
 *          $this->setModel(['class' => usersCreate, 'scenario' => UsersCreate::SCENARIO_DEFAULT);
 *          $this->assertUniqueAttribute('mail');
 *          $this->setModel(['class' => usersCreate, 'scenario' => UsersCreate::SCENARIO_ADMIN);
 *          $this->assertUniqueAttribute('mail');
 *      }
 * }
 * </code>
 *
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class ModelTestCase extends \pyd\testkit\TestCase
{
    /**
     * A 'base' instance of the model under test.
     * 
     * This instance is not modified during the tests. It is meant to be cloned
     * when needed by {@see getModel()}.
     * 
     * @var \yii\base\Model
     */
    private $_model;

    /**
     * Return the {@see $_model} base model instance.
     * 
     * If the {@see $_model} property has not been initialized already, it will
     * be set using the {@see modelReference()} method.
     * 
     * @return \yii\base\Model
     */
    public function getModel()
    {
        if (null === $this->_model) {
            $exceptionBaseMessage = "Cannot provide the model instance to be tested.";
            try {
                $this->setModel($this->modelReference());
            } catch (InvalidCallException $callException) {
                throw new InvalidCallException($exceptionBaseMessage . ' ' . $callException->getMessage());
            } catch (InvalidConfigException $configException) {
                throw new InvalidCallException($exceptionBaseMessage . ' ' . $configException->getMessage());
            }
        }
        return $this->_model;
    }

    /**
     * Set the {@see $_model} property.
     * 
     * This will set the {@see $_model} property for the currently executed test
     * case method only.
     * 
     * @see \yii\di\Instance::ensure()
     * @param object|string|array|static $reference
     */
    public function setModel($reference)
    {
        $this->_model = Instance::ensure($reference, Model::className());
    }

    /**
     * Set the {@see $_model} to null.
     */
    public function clearModel()
    {
        $this->$_model = null;
    }

    /**
     * Provide a 'reference' {@see \yii\di\Instance::ensure()} of the model under
     * test.
     * 
     * @return object|string|array|static
     */
    public function modelReference()
    {
        throw new InvalidCallException("The " . get_class($this) . '::' . __FUNCTION__ . "() method is not implemented.");
    }
    
    /**
     * Check 'safe' attributes.
     * 
     * @param array $attributes names of the attributes that should be 'safe'
     * @param \yii\base\Model $model
     */
    public function assertSafeAttributes(array $attributes, Model $model = null)
    {
        $assertionMessage = '';
        if (null === $model) {
            $model = clone $this->getModel();
        }
        $safe = $model->safeAttributes();
        $unexpected = array_diff($safe, $attributes);
        $missing = array_diff($attributes, $safe);
        if ([] !== $unexpected) {
            $assertionMessage .= "Model has unexpected safe attribute(s): [" . implode(', ', array_values($unexpected)) . "].\n";
        }
        if ([] !== $missing) {
            $assertionMessage .= "Model does not have safe attribute(s) [" . implode(', ', array_values($missing)) . "].\n";
        }

        $this->assertTrue('' === $assertionMessage, $assertionMessage);
    }
    
    /**
     * Check 'required' attributes among active ones.
     * 
     * @param array $attributes names of the attributes that should be 'required'
     * @param \yii\base\Model $model
     */
    public function assertRequiredAttributes(array $attributes, Model $model = null)
    {
        $assertionMessage = '';
        $required = $this->getRequiredActiveAttributes($model);
        $unexpected = array_diff($required, $attributes);
        $missing = array_diff($attributes, $required);
        if ([] !== $unexpected) {
            $assertionMessage .= "Model has unexpected required attribute(s) [" . implode(', ', array_values($unexpected)) . "].\n";
        }
        if ([] !== $missing) {
            $assertionMessage .= "Model does not have required attribute(s) [" . implode(', ', array_values($missing)) . "].\n";
        }

        $this->assertTrue('' === $assertionMessage, $assertionMessage);
    }
    
    /**
     * Check 'active' attributes.
     * 
     * @param array $attributes names of the attributes that should be 'active'
     * @param \yii\base\Model $model
     */
    public function assertActiveAttributes(array $attributes, Model $model = null)
    {
        $assertionMessage = '';
        if (null === $model) {
            $model = clone $this->getModel();
        }
        $active = $model->activeAttributes();
        $unexpected = array_diff($active, $attributes);
        $missing = array_diff($attributes, $active);
        if ([] !== $unexpected) {
            $assertionMessage .= "Model has unexpected active attribute(s): [" . implode(', ', array_values($unexpected)) . "].\n";
        }
        if ([] !== $missing) {
            $assertionMessage .= "Model does not have active attribute(s) [" . implode(', ', array_values($missing)) . "].\n";
        }

        $this->assertTrue('' === $assertionMessage, $assertionMessage);
    }
    
    /**
     * Check if one or more attributes must be unique.
     * 
     * @param string|[] $attribute name(s) of the attribute(s) that should be 'active'
     * @param string|[] $targetAttribute name(s) of the attribute(s) that should
     * have validation error with non unique value(s)
     * @param \yii\base\Model $model
     * @throws InvalidCallException cannot get a record from db to extract
     * existing value(s)
     */
    public function assertUniqueAttribute($attribute, $targetAttribute = null, Model $model = null)
    {
        if (null === $model) {
            $model = clone $this->getModel();
        }
        
        // need existing - already present in db table - value(s) to check that
        // validation of the tested model fails with these
        $modelClassName = get_class($model);
        $countTableRows = $modelClassName::find()->count();
        if (0 === $countTableRows || (!$model->getIsNewRecord() && $countTableRows < 2 )) {
            throw new InvalidCallException("Db table ' " .$modelClassName::tablename(). "'"
                    . " has not enough rows to check if attribute(s) must be unique.");
        }
        
        // existing value(s) will be extracted from an existing record
        if ($model->getIsNewRecord()) {
            // this can be any record if the tested model is a new record
            $existingModel = $modelClassName::find()->one();
        } else {
            /*  @var \yii\db\ActiveQuery $query */
            $query = $modelClassName::find();
            foreach ($modelClassName::primaryKey() as $pk) {
                $query->where[] = ['<>', $pk, $model->$pk];
            }
            $existingModel = $query->one();
        }
        
        // initialize model unique attribute(s) with existing value(s)
        $uniqueAttributes = is_array($attribute) ? $attribute : [$attribute];
        foreach ($uniqueAttributes as $attributeToSet) {
            $model->$attributeToSet = $existingModel->$attributeToSet;
        }
        
        if (null === $targetAttribute) {
            $targetAttributes = $uniqueAttributes;
        } else {
            $targetAttributes = is_array($targetAttribute) ? $targetAttribute : [$targetAttribute];
        }
       
        $this->assertFalse($model->validate($targetAttributes), "Validation"
                . " should fail for attribute(s) [" .implode(', ', $uniqueAttributes). "]"
                . " with non unique value(s) [" . implode(', ', $existingModel->getAttributes($uniqueAttributes)). "].");
    }
    
    /**
     * Check if an attribute is validated only with serial values.
     * 
     * @param string $attribute
     * @param \yii\base\Model $model
     */
    public function assertSerialAttribute($attribute, Model $model = null)
    {
        $validValues = ['1', 1];
        $invalidValues = ['ten', 'a'];
        
        if (null === $model) {
            $model = clone $this->getModel();
        }
        
        foreach ($validValues as $validValue) {
            $model->$attribute = $validValue;
            $this->assertTrue($model->validate($attribute),
                    "Validation should not fail for serial attribute '$attribute' with value (" .gettype($validValue). ") '$validValue'.");
        }
        
        foreach ($invalidValues as $invalidValue) {
            $model->$attribute = $invalidValue;
            $this->assertFalse($model->validate($attribute),
                    "Validation should fail for serial attribute '$attribute' with value (" .gettype($invalidValue). ") '$invalidValue'.");
        }
    }
    
    /**
     * Check if a value is valid for an attribute.
     *
     * This method will set target attribute with a value - eventually other
     * attributes too - and verify, after calling validate(), if the model
     * has errors for this attribute.
     *
     * @param string $attribute attribute name
     * @param int|string $value value to verify
     * @param array $otherAttributes attribute/value pairs to initialize other
     * model attributes
     * @param \yii\base\Model $model
     * @return boolean
     */
    public function attributeValueIsValid($attribute, $value, $otherAttributes = [], $model = null)
    {
        if (null === $model) {
            $model = clone $this->getModel();
        }
        // set $otherAttributes first in case it contains - by error - a value for $attribute
        $model->setAttributes($otherAttributes, false);
        $model->$attribute = $value;
        $model->validate();
        if (!$model->hasErrors($attribute)) {
            AssertionMessage::set("Value '$value' is valid for attribute '$attribute'.");
            return true;
        } else {
            AssertionMessage::set("Value '$value' is not valid for attribute '$attribute'.
                Error message is : '" .  end($model->getErrors($attribute)). "'.");
            return false;
        }
    }

    /**
     * Check 'active' attributes validation.
     *
     * The goal is to check for each 'active' attribute that validation fails
     * with invalid values and vice versa.
     *
     * Validation data format:
     * - key must be an attribute name;
     * - value can be an array or FALSE if the attribute has not to be checked;
     * 
     * Attribute data format:
     * - must have 2 keys, 'valid' and 'invalid';
     * - value can be an array or FALSE if the attribute has not to be check
     * checked with 'valid' and/or 'invalid' values;
     * 
     * Arrays of valid or invalid data format:
     * - each item can be a single value or an array;
     * - if an array it must contain a 'value' key with the attribute value and
     * can contain an 'otherAttributes' key which value is an array of attributes
     * name => value pairs;
     *
     * <code>
     * $validationData = [
     *      'username' => [
     *          'valid' => ['username1', 'username2'],
     *          'invalid' => ['short', 'notUniqueUsername']
     *      ],
     *      'password' => [
     *          'valid' => ['shortest', 'longuestPassword'],
     *          'invalid' => ['short']
     *      ],
     *      'passwordConfirm' => [
     *          // the 'password' attribute must be initialized to check the
     *          // 'passwordConfirm' one
     *          'valid' => [
     *              ['value' => 'validPassword', 'otherAttributes' => ['password' => 'validPassword']
     *          ]
     *          'invalid' => [
     *              'noPasswordSet',    // validation will fail because 'password' attribute has no value
     *              ['value' => 'onePassword', 'otherAttributes' => ['password' => 'otherPassword']]
     *          ]
     *      ],
     *      // the 'comment' attribute will be checked with valid values only
     *      'comment' => ['valid' => ['Valid comment'], 'invalid' => FALSE],
     *      // the 'created_at' attribute won't be checked at all
     *      'created_at' => false,
     * ];
     * </code>
     * 
     * Note: each attribute validation
     *
     * @param array $validationData
     * @param \yii\base\Model $model
     * @throws InvalidParamException
     */
    public function assertValidationDataMatchesValidationRules(array $validationData, Model $model = null)
    {
        if (null === $model) {
            $model = clone $this->getModel();
        }
        $errorMessages = '';

        foreach ($model->activeAttributes() as $attribute) {

            // by default each 'active' attribute must be checked ...
            if (!array_key_exists($attribute, $validationData)) {
                throw new InvalidParamException("Missing validation data for attribute '$attribute'.
                    To skip verification for this attribute use '$attribute' => FALSE.");
            }
            // unless its validation data is set to false
            if (false === $validationData[$attribute]) continue;

            // if an array, validation data for an attribute must have 'valid'
            // and 'invalid' keys
            if (!is_array($validationData[$attribute])
                    || !array_key_exists('valid', $validationData[$attribute])
                    || !array_key_exists('invalid', $validationData[$attribute])) {
                throw new InvalidParamException("Invalid validation data for attribute '$attribute'.
                        It must be an array with 'valid' and 'invalid' keys.");
            }

            // valid validation data must be an array or FALSE. If the latter
            // validation with valid values will be skipped for this attribute
            if (false !== $validationData[$attribute]['valid']) {

                foreach ($validationData[$attribute]['valid'] as $validData) {

                    // valid data can contain a single value or an array. If the
                    // latter it must have 'value' and 'otherAttributes' keys.
                    $value = (is_array($validData)) ? $validData['value'] : $validData;
                    $otherAttributes = (is_array($validData)) ? $validData['otherAttributes'] : [];
                    if (!$this->attributeValueIsValid($attribute, $value, $otherAttributes)) {
                        $errorMessages = "\nValidation should not fail for attribute '$attribute' with value $value.
                                Error message is : '" .  end($model->getErrors($attribute)). "'.";
                    }
                }
            }

            // invalid validation data must be an array or FALSE. If the latter
            // validation with invalid values will be skipped for this attribute
            if (false !== $validationData[$attribute]['invalid']) {

                foreach ($validationData[$attribute]['invalid'] as $invalidData) {

                    // invalid data can contain a single value or an array. If
                    // the latter it must have 'value' and 'otherAttributes' keys.
                    $value = (is_array($invalidData)) ? $invalidData['value'] : $invalidData;
                    $otherAttributes = (is_array($invalidData)) ? $invalidData['otherAttributes'] : [];
                    if ($this->attributeValueIsValid($attribute, $value, $otherAttributes)) {
                        $errorMessages .= "\nValidation should fail for attribute '$attribute' with value $value.";
                    }
                }
            }
            
        }
        self::assertTrue('' === $errorMessages, $errorMessages);
    }
    
    /**
     * Return the 'required' 'active' attributes of the model.
     *
     * @param \yii\base\Model|null $model instance of the model to test
     * @return array
     */
    protected function getRequiredActiveAttributes($model = null)
    {
        if (null === $model) {
            $model = $this->getModel();
        }
        $attributes = [];
        $activeAttributes = $model->activeAttributes();
        foreach ($model->getActiveValidators() as $validator) {
            if ($validator instanceof \yii\validators\RequiredValidator) {
                $requiredActiveAttributes = array_intersect($activeAttributes, $validator->attributes);
                $attributes = array_merge($attributes, $requiredActiveAttributes);
            }
        }
        return $attributes;
    }
}
