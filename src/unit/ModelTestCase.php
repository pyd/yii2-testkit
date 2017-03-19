<?php
namespace pyd\testkit\unit;

use yii\base\Model;
use yii\di\Instance;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\base\UnknownPropertyException;

/**
 * Base class for models unit tests.
 *
 * <code>
 * class UserCreateTest extends \pyd\testkit\unit\ModelTestCase
 * {
 *      public function modelReference()
 *      {
 *          return app\models\user\Create::className();
 *          // or
 *          // return ['class' => app\models\User::className(), 'scenario' => 'create'];
 *      }
 *
 *      public static function validationData()
 *      {
 *          return [
 *              'firstname' => ['valid' => ['Franck', 'John William'], 'invalid' => ['', 'Oscar33']],
 *              'lastname'  => ['valid' => ['Del Mar', "O'Crohan"], 'invalid' => ['', 'Von-Stemberg']],
 *              'username' => ['valid' => ['valid_username'], 'invalid' => ['2short', '0123456789'],
 *              'password' => ['valid' => ['valid.password'], 'invalid' => ['', '2short'],
 *              'mail' => [...],
 *              'birthDate' => [...],
 *              'created_at' => [...],
 *              'is_admin' => ['valid' => [0, 1,'0', '1'], 'invalid => ['', '2', 3]
 *          ];
 *      }
 *
 *      public function testValidation()
 *      {
 *          $this->assertSafeAttributesAre(['firstname', 'lastname', 'username', 'password', 'mail', 'birthDate]);
 *          $this->assertActiveAttributesAre(['firstname', 'lastname', 'username', 'password', 'mail', 'birthDate', 'created_at', 'is_admin]);
 *          $this->assertRequiredAttributesAre(['firstname', 'lastname', 'username', 'password', 'mail']);
 *          $this->assertValidationDataMatchValidationRules(self::validationData());
 *      }
 *
 *      public function testDefaultValues()
 *      {
 *          $model = $this->getModel();
 *          $model->firstname = 'Mary';
 *          $model->lastname = 'Da Costa',
 *          $model->username = 'marydacosta',
 *          $model->password = 'cosmadaryta',
 *          $this->assertTrue($model->save());
 *          $model->refresh();
 *          $this->assertEquals(date('Y-m-d'), $model->created_at);
 *          $this->assertFalse($model->is_admin);
 *      }
 * }
 * </code>
 *
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class ModelTestCase extends \pyd\testkit\base\TestCase
{
    /**
     * @var \yii\base\Model instance of the model under test
     */
    private $model;

    /**
     * @see $model
     * @return \yii\base\Model
     */
    public function getModel()
    {
        if (null === $this->model) {
            $this->setModel($this->modelReference());
        }
        return $this->model;
    }

    /**
     * @see $model
     * @see \yii\di\Instance::ensure()
     * @param object|string|array|static $reference
     */
    public function setModel($reference)
    {
        $this->model = Instance::ensure($reference, Model::className());
    }

    /**
     * Reference used to create the model to test.
     *
     * @see $setModel
     * @return object|string|array|static
     * @throws InvalidCallException
     */
    public function modelReference()
    {
        throw new InvalidCallException("You must implement the " . __METHOD__ . "() method.");
    }


    /**
     * A set of valid and invalid values to test the validation rules.
     *
     * return [
     *      'username' => [
     *          'valid' => ['tomcat55', 'roberto'],
     *          'invalid' => ['short']
     *      ],
     *      'password' => [
     *          'valid' => [...],
     *          'invalid' => [...]
     *      ],...
     * ];
     * @return array
     */
    public static function validationData()
    {
        throw new InvalidCallException("You must implement the " . __METHOD__ . "() method.");
    }

    /**
     * Get a valid value for each attribute.
     *
     * @return array ['attribute1' => $attribute1ValidValue, 'attribute2' => $attribute2ValidValue,...]
     */
    public static function getAttributesValidValue()
    {
        $validData = [];
        foreach (static::validationData() as $attribute => $data) {
            $validData[$attribute] = $data['valid'][0];
        }
        return $validData;
    }

    /**
     * This method will verify that, for each active attribute:
     * - validation succeeds with 'valid' values;
     * - validation fails with 'invalid' values;
     *
     * @param array $validationData valid and invalid data to verify the validation
     * rules. If an empty array (default) @see validationData() will be called
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     * @throws InvalidParamException validation data is missing or invalid
     */
    public function assertValidationDataMatchValidationRules(array $validationData = [], Model $model = null)
    {
        if ([] === $validationData) $validationData = static::validationData();
        if (null === $model) $model = $this->getModel();
        $failureMessages = '';

        // each 'active' attribute must have its validation rules verified
        foreach ($model->activeAttributes() as $attribute) {

            // one needs 'valid' and 'invalid' data to verify this attribute rules
            if (!array_key_exists($attribute, $validationData) || !is_array($validationData[$attribute])) {
                throw new InvalidParamException("Missing or invalid (an array is expected) validation data for attribute '$attribute'.");
            }

            $attributeValidationData = $validationData[$attribute];

            if (!array_key_exists('valid', $attributeValidationData) || !is_array($attributeValidationData['valid'])) {
                throw new InvalidParamException("Missing or invalid (an array is expected) 'valid' validation data for attribute '$attribute'.");
            }

            if (!array_key_exists('invalid', $attributeValidationData) || !is_array($attributeValidationData['valid'])) {
                throw new InvalidParamException("Missing or invalid (an array is expected) 'invalid' validation data for attribute '$attribute'.");
            }

            $message = $this->assertAttributeValidationSucceedsWithValidValues($attribute, $attributeValidationData['valid'], $model, true);
            if ('' !== $message) $failureMessages .= "\n" . $message;

            $message = $this->assertAttributeValidationFailsWithInvalidValues($attribute, $attributeValidationData['invalid'], $model, true);
            if ('' !== $message) $failureMessages .= "\n" . $message;
        }

        $this->assertTrue('' === $failureMessages, $failureMessages);
    }

    /**
     * Verify that an attribute validation succeeds with 'valid' values.
     *
     * @param string $attribute attribute name
     * @param array $values valid values for this attribute
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     * @param boolean $returnMessage force to return failure message(s). For
     * internal use, when this method is called by @see assertValidationDataMatchValidationRules
     */
    public function assertAttributeValidationSucceedsWithValidValues($attribute, array $values = [], Model $model = null, $returnMessage = false)
    {
        if ([] === $values) $values = self::validationData()[$attribute];
        if (null === $model) $model = $this->getModel();
        $failureMessages = '';

        foreach ($values as $value) {
            $model->$attribute = $value;
            if (!$model->validate([$attribute])) {
                $failureMessages .= "Validation should not fail for attribute '$attribute' with value '$value'";
                $failureMessages .= "\n\tModel error : " . end($model->getErrors($attribute));
            }
        }

        if ($returnMessage) return $failureMessages;

        $this->assertTrue('' === $failureMessages, $failureMessages);
    }

    public function valueIsValidForAttribute($attribute, $value, $otherAttributes = [])
    {
        
    }

    /**
     * Verify that an attribute validation fails with 'invalid' values.
     *
     * @param string $attribute attribute name
     * @param array $values invalid values for this attribute
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     * @param boolean $returnMessage force to return failure message(s). For
     * internal use, when this method is called by @see assertValidationDataMatchValidationRules
     */
    public function assertAttributeValidationFailsWithInvalidValues($attribute, array $values = [], Model $model = null, $returnMessage = false)
    {
        if ([] === $values) $values = self::validationData()[$attribute];
        if (null === $model) $model = $this->getModel();
        $failureMessages = '';

        foreach ($values as $value) {
            $model->$attribute = $value;
            if ($model->validate([$attribute])) {
                $failureMessages .= "Validation should fail for attribute '$attribute' with value '$value'.";
            }
        }

        if ($returnMessage) return $failureMessages;

        $this->assertTrue('' === $failureMessages, $failureMessages);
    }

    /**
     * Verify that an attribute validation fails with a value that already
     * exists in db.
     *
     * @param string $attribute attribute name
     * @param \yii\db\BaseActiveRecord $model an instance of the tested model.
     * If null, an instance will be provided by the @see getModel() method.
     */
    public function assertAttributeMustHaveUniqueValue($attribute, \yii\db\BaseActiveRecord $model = null)
    {
        if (null === $model) { $model = $this->getModel(); }

        // get an existing value from db for this attribute
        $query = (new \yii\db\Query())
            ->select($attribute)
            ->from($model->tableName())
            ->limit(1);
        if (!$model->getIsNewRecord()) {
            // attribute value must be different from the model one
            $query->where("$attribute!='{$model->$attribute}'");
        }
        $existingValue = $query->scalar();
        if (false === $existingValue) {
            throw new InvalidCallException("Cannot get an existing value for attribute '$attribute' "
                    . " in table {$model->tableName()}. You should add a row of fixture data for this table.");
        }

        $model->$attribute = $existingValue;
        $this->assertFalse($model->validate([$attribute]), "Validation should fail for attribute '$attribute' with non unique value '{$model->$attribute}'.");
    }

    /**
     * Verify that $attributes matches the model 'safe' attribute names.
     *
     * @param array $attributes attribute names
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     */
    public function assertSafeAttributesAre(array $attributes, Model $model = null)
    {
        $this->assertAttributesAre('safe', $attributes, $model);
    }

    /**
     * Verify that $attributes matches the model 'active' attribute names.
     *
     * @param array $attributes attribute names
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     */
    public function assertActiveAttributesAre(array $attributes, Model $model = null)
    {
        $this->assertAttributesAre('active', $attributes, $model);
    }

    /**
     * Verify that $attributes matches the model 'required' attribute names.
     *
     * Required attributes are the ones that use the RequiredValidator.
     *
     * @param array $attributes attribute names
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     */
    public function assertRequiredAttributesAre($attributes, Model $model = null)
    {
        $this->assertAttributesAre('required', $attributes, $model);
    }

    /**
     * Verify that attributes are of type $type.
     *
     * @param string $type the expected type ('safe', 'active', 'required') of the attributes
     * @param array $attributes attribute names
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     * @throws InvalidParamException
     */
    protected function assertAttributesAre($type, array $attributes, Model $model = null)
    {
        if (null === $model) $model = $this->getModel();
        $failureMessages = [];

        switch ($type) {
            case 'safe':
                $modelAttributes = $model->safeAttributes();
                break;
            case 'active':
                $modelAttributes = $model->activeAttributes();
                break;
            case 'required':
                $modelAttributes = $this->getRequiredActiveAttributes($model);
                break;
            default:
                throw new InvalidParamException("Unsupported type '$type'.");
        }

        $foundNotExpected = array_diff($modelAttributes, $attributes);
        $expectedNotFound = array_diff($attributes, $modelAttributes);

        if ([] !== $foundNotExpected) {
            $failureMessages[] = "Attribute(s) [" . implode(', ', array_values($foundNotExpected)) . "] should not be $type in the model.\n";
        }

        if ([] !== $expectedNotFound) {
            $failureMessages[] = "Attribute(s) [" . implode(', ', array_values($expectedNotFound)) . "] should be $type in the model.\n";
        }

        $this->assertTrue([] === $failureMessages, implode("\n", $failureMessages));
    }

    /**
     * Return the 'required' 'active' attributes of the model.
     *
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     * @return array
     */
    protected function getRequiredActiveAttributes(Model $model = null)
    {
        if (null === $model) $model = $this->getModel();
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

    /**
     * Verify that empty values are allowed for $attributes.
     *
     * @param array $attributes attribute names
     * @param \yii\base\Model $model an instance of the model to test. If null
     * the instance will be provided by @see getModel()
     */
    public function assertAttributesAllowingEmptyValueAre(array $attributes, Model $model = null)
    {
        if (null === $model) $model = $this->getModel();
        $failureMessages = [];

        foreach ($attributes as $attribute) {
            $model->$attribute = '';
            if (!$model->validate([$attribute])) {
                $failureMessages[] = "Validation should not fail for attribute '$attribute' with an value.";
            }
        }
        $this->assertTrue([] === $failureMessages, implode("\n", $failureMessages));
    }
}
