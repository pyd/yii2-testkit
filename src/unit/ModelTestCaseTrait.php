<?php
namespace pyd\testkit\unit;

use yii\base\Model;
use yii\di\Instance;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\base\UnknownPropertyException;

/**
 * Base class to test Model objects.
 *
 * <code>
 * class UserCreateTest extends \pyd\testkit\unit\ModelTestCase
 * {
 *      public function getModelReference()
 *      {
 *          return app\models\user\Create::className();
 *          // or
 *          // return ['class' => app\models\User::className(), 'scenario' => 'create'];
 *      }
 *
 *      public function testValidation()
 *      {
 *          $this->assertSafeAttributesAre(['firstname', 'lastname', 'username', 'password', 'mail', 'birthDate]);
 *          $this->assertActiveAttributesAre(['firstname', 'lastname', 'username', 'password', 'mail', 'birthDate', 'created_at', 'is_admin]);
 *          $this->assertRequiredAttributesAre(['firstname', 'lastname', 'username', 'password', 'mail']);
 *          $this->assertValuesMatchValidationRules(self::validationData());
 *      }
 *
 *      public function testDefaultValues()
 *      {
 *          $model = $this->model;
 *          $model->firstname = 'Mary';
 *          $model->lastname = 'Da Costa',
 *          $model->username = 'marydacosta',
 *          $model->password = 'cosmadaryta',
 *          $this->assertTrue($model->save());
 *          $mode->refresh();
 *          $this->assertEquals(date('Y-m-d'), $model->created_at);
 *          $this->assertFalse($model->is_admin);
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
 * }
 * </code>
 *
 * A new model instance {@see $model} is created for each test {@see setup} if
 * {@see $modelAutoSet} is set to true.
 *
 * If this behavior doesn't fit your needs, set {@see $modelAutoSet} to false
 * but don't forget to initialize the {@see $model} property required by most of
 * this class methods. You don't have to implement the {@see getModelReference}
 * method in that case.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
trait ModelTestCaseTrait
{
    /**
     * @var \yii\base\Model an instance of the tested class {@see setUp}
     */
    protected $model;
    /**
     * A new model instance is created before each test method execution
     * {@see setUp}. If set to true, you must define a reference of the model
     * with {@see getModelReference}.
     *
     * @var boolean
     */
    protected $modelAutoSet = true;

    /**
     * This is an array of - valid and invalid - values for the model attributes.
     *
     *
     * @return array
     * @throws InvalidCallException
     */
    abstract public static function validationData();

    /**
     * Initialize model {@see $model} if needed {@see $modelAutoSet}.
     */
    public function setUp()
    {
        parent::setUp();
        if ($this->modelAutoSet) $this->setModel($this->getModelReference());
    }


    public function setModel($model)
    {
        $this->model = Instance::ensure($model, Model::className());
    }

    /**
     * Returns model reference {@link yii\base\Instance::ensure}.
     *
     * @return string|array a model class name or configuration array.
     */
    protected function getModelReference()
    {
        throw new InvalidCallException("You must implement a " .get_class($this). "::getModelReference method.");
    }

    /**
     * This method will verify that, for each active attribute:
     * - validation is successful with valid values;
     * - validation fail with invalid values;
     *
     * This method will not check if you provide valid and invalid value(s) for
     * each active attribute.
     * You should define at least one valid and one invalid value for each
     * attribute of the model {@see validationData} and use it's return as
     * param.
     *
     * @param array $values {@see validationData}
     */
    public function assertValuesMatchValidationRules(array $values)
    {
        $activeAttributes = $this->model->activeAttributes();
        $assertionsMessages = '';

        foreach ($values as $attribute => $attributeValues) {

            if (in_array($attribute, $activeAttributes)) {

                if (isset($attributeValues['valid'])) {
                    $message = $this->assertAttributeValuesAreValid($attribute, $attributeValues['valid'], '', true);
                    if ('' !== $message) $assertionsMessages .= "\n" . $message;
                }

                if (isset($attributeValues['invalid'])) {
                    $message = $this->assertAttributeValuesAreInvalid($attribute, $attributeValues['invalid'], '', true);
                    if ('' !== $message) $assertionsMessages .= "\n" . $message;
                }
            }
        }

        $this->assertTrue('' === $assertionsMessages, $assertionsMessages);
    }

    /**
     * Verify that validation fails for attributes when value already exists in
     * db.
     *
     * @param array $uniqueAttributesWithExistingValues list of unique attributes
     * with value already in db ['username' => 'existingValue, ...]
     * @param \yii\base\Model $model an instance of the model to test if different
     * of the one created using @see getModelReference
     * @throws InvalidParamException
     */
    public function assertAttributesMustBeUnique(array $uniqueAttributesWithExistingValues, $model = null)
    {
        if (null === $model) $model = $this->model;
        if (null === $model) {
            throw new InvalidCallException("You must define the " . get_class() . "::\$model property"
                    . " or pass a Model instance as a parameter of the " . __METHOD__ . " method.");
        }

        $badAttributes = [];
        foreach ($uniqueAttributesWithExistingValues as $attribute => $existingValue) {
            $model->$attribute = $existingValue;
            // attribute should not validate with existing value
            if (false !== $model->validate([$attribute])) $badAttributes[] = $attribute;
        }
        $this->assertTrue([] === $badAttributes, "Validation should fail for attributes ["
                . implode(', ', $badAttributes) . "] with non unique value.");
    }

    /**
     * Verify which attributes are 'safe' in the model.
     *
     * <code>
     * public function testConnectionValidation()
     * {
     *      $this->setScenarion('connection');
     *      $this->assertSafeAttributesAre(['username', 'password']);
     * }
     * </code>
     *
     *
     * @param array $attributes names of all attributes that should be 'safe' in the model.
     * @param string $message assertion message argument. If empty, a custom msg will be generated.
     */
    public function assertSafeAttributesAre(array $attributes, $message = '')
    {
        $this->assertAttributesAre('safe', $attributes, $message);
    }

    /**
     * Verify which attributes are 'active' in the model.
     *
     * <code>
     * public function testConnectionValidation()
     * {
     *      $this->setScenarion('connection');
     *      $this->assertSafeAttributesAre(['username', 'password']);
     *      $this->assertActiveAttributesAre(['username', 'password']);
     * }
     * </code>
     *
     * @param array $attributes names of all attributes that should be 'active' in the model.
     * @param string $message assertion message argument. If empty, a custom msg will be generated.
     */
    public function assertActiveAttributesAre(array $attributes, $message = '')
    {
        $this->assertAttributesAre('active', $attributes, $message);
    }

    /**
     * Verify which attributes are 'required' (have a required validator) by the model.
     *
     * <code>
     * public function testConnectionValidation()
     * {
     *      $this->setScenarion('connection');
     *      $this->assertSafeAttributesAre(['username', 'password']);
     *      $this->assertActiveAttributesAre(['username', 'password']);
     *      $this->assertRequiredAttributesAre(['username', 'password']);
     * }
     * </code>
     *
     * @param array $attributes names of all attributes that are 'required' by the model.
     * @param string $message assertion message argument. If empty, a custom msg will be generated.
     */
    public function assertRequiredAttributesAre($attributes, $message = '')
    {
        $this->assertAttributesAre('required', $attributes, $message);
    }

    /**
     *
     * @param string $type the expected type ('safe', 'active', 'required') of the attributes
     * @param array $attributes attribute names
     * @param string $message assertion message argument. If empty, a custom msg will be generated.
     * @throws InvalidParamException
     */
    protected function assertAttributesAre($type, array $attributes, $message = '')
    {
        switch ($type) {

            case 'safe':
                $modelAttributes = $this->model->safeAttributes();
                break;

            case 'active':
                $modelAttributes = $this->model->activeAttributes();
                break;

            case 'required':
                $modelAttributes = $this->getRequiredActiveAttributes();
                break;

            default:
                throw new InvalidParamException("Unsupported type '$type'.");
        }

        $foundNotExpected = array_diff($modelAttributes, $attributes);
        $expectedNotFound = array_diff($attributes, $modelAttributes);

        if ('' === $message) {

            if ([] !== $foundNotExpected) {
                $message = "Attribute(s) [" . implode(', ', array_values($foundNotExpected)) . "] should not be $type in the model.";
            }

            if ([] !== $expectedNotFound) {
                $message .= "Attribute(s) [" . implode(', ', array_values($expectedNotFound)) . "] should be $type in the model.";
            }
        }

        $this->assertTrue([] === $foundNotExpected && [] === $expectedNotFound, $message);
    }

    /**
     * Return a list of 'active' attributes associated with a 'required' validator.
     *
     * @todo detect if Validator::$when property is defined and validation should
     * apply.
     *
     * @return array {@see $model} active attributes associated with a 'required'
     * validator.
     */
    protected function getRequiredActiveAttributes()
    {
        $attributes = [];
        $activeAttributes = $this->model->activeAttributes();
        foreach ($this->model->getActiveValidators() as $validator) {
            if ($validator instanceof \yii\validators\RequiredValidator) {
                $requiredActiveAttributes = array_intersect($activeAttributes, $validator->attributes);
                $attributes = array_merge($attributes, $requiredActiveAttributes);
            }
        }
        return $attributes;
    }

    /**
     * Verify that validation succeed with values $values for attribute $attribute.
     *
     * @param string $attribute attribute name
     * @param array $values valid values
     * @param string $message assertion message argument. If empty, a custom msg will be generated.
     */
    public function assertAttributeValuesAreValid($attribute, array $values, $message = '', $returnMessage = false)
    {
        $model = $this->model;
        $badValues = [];

        foreach ($values as $val) {

            $model->$attribute = $val;

            if (!$model->validate([$attribute])) {
                if ('' === $val) $val = 'empty string';
                $badValues[] = $val;
            }
        }

        if ([] !== $badValues && '' === $message) {
            $message = "Validation should not fail for attribute '$attribute' with value(s) [" . implode(', ', $badValues) . '].';
            $message .= "\n\tModel error : " . end($this->model->getErrors($attribute));
        }

        if ($returnMessage) {
            return $message;
        }

        $this->assertTrue([] === $badValues, $message);
    }

    /**
     * Verify that validation fail with values $values for attribute $attribute.
     *
     * @param string $attribute attribute name
     * @param array $values attribute expected-invalid values
     * @param string $message assertion message argument. If empty, a custom msg will be generated.
     */
    public function assertAttributeValuesAreInvalid($attribute, array $values, $message = '', $returnMessage = false)
    {
        $model = $this->model;
        $badValues = [];

        foreach ($values as $val) {

            $model->$attribute = $val;
            if ($model->validate([$attribute])) {
                if ('' === $val) $val = "empty string";
                $badValues[] = $val;
            }
        }

        if ([] !== $badValues && '' === $message) {
            $message = "Validation should fail for attribute '$attribute' with values (" . implode(', ', $badValues) . ').';
        }

        if ($returnMessage) {
            return $message;
        }

        $this->assertTrue([] === $badValues, $message);
    }

    public function assertEmptyValueAllowedAttributesAre(array $attributes)
    {
        $model = $this->model;
        $badAttributes = [];

        foreach ($attributes as $attribute) {
            $model->$attribute = '';
            if (!$model->validate([$attribute])) {
                $badAttributes[] = $attribute;
            }
        }
        $this->assertTrue([] === $badAttributes, "Validation should not fail for attributes (" .  implode(', ', $badAttributes). ") with empty value.");
    }
}
