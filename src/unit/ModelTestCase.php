<?php
namespace pyd\testkit\unit;

use yii\base\Model;
use yii\di\Instance;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\base\UnknownPropertyException;
use pyd\testkit\AssertionMessage;

/**
 * Base class for models unit tests.
 *
 * Usage:
 * - create a test case extending this class;
 * - set model to be tested using @see setModel() - in each test method - or by
 * implementing @see modelReference()
 *
 * <code>
 * class UserCreateTest extends \pyd\testkit\unit\ModelTestCase
 * {
 *      public function modelReference()
 *      {
 *          return ['class' => app\models\User::className(), 'scenario' => 'create'];
 *      }
 *
 *      public static function validationData()
 *      {
 *          return [
 *              'firstname' => ['valid' => ['Franck', 'John William'], 'invalid' => ['', 'Oscar33']],
 *              'lastname'  => ['valid' => ['Del Mar', "O'Crohan"], 'invalid' => ['', 'Von-Stemberg']],
 *              'username' => ['valid' => ['valid_username'], 'invalid' => ['2short', '0123456789'],
 *              'password' => ['valid' => ['valid.password'], 'invalid' => ['', '2short'],
 *              'password_confirm => [
 *                  'valid' => [
 *                      // use an array to set other attributes values
 *                      // 'value' contains the password_confirm attribute value
 *                      ['value' => 'shortpwd' 'otherAttributes' => ['password' => 'shortpwd']]
 *                  ],
 *                  'invalid' => [
 *                      // password is not set
 *                      'shortpwd',
 *                      // password is not confirmed
 *                      ['value' => 'shortpwd', 'otherAttributes' => ['password' => 'shortpXd]]
 *                  ]
 *              ]
 *              'mail' => [...],
 *              'birth_date' => [...],
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
 *          $this->assertValidationDataMatchesValidationRules(self::validationData());
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
     * Clear the instance of the model under test.
     *
     * @see $model
     */
    public function clearModel()
    {
        $this->model = null;
    }

    /**
     * Return a "reference" of the model under test.
     *
     * Used by @see setModel() to create a model instance if it's not initialized.
     *
     * @see $getModel
     * @see $setModel
     * @return object|string|array|static
     */
    public function modelReference()
    {
        throw new InvalidCallException("You must implement the " . __METHOD__ . "() method.");
    }

    /**
     * Verify that a value is valid for an attribute.
     *
     * This method will set target attribute with a value - eventually other
     * attributes too - and verify, after calling validate(), if the model
     * has errors for this attribute.
     *
     * @param string $attribute attribute name
     * @param int|string $value value to verify
     * @param array $otherAttributes attribute/value pairs to initialize other
     * model attributes
     *
     * @return boolean
     */
    public function attributeValueIsValid($attribute, $value, $otherAttributes = [])
    {
        $model = clone $this->getModel();
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
     * Verify validation rules with valid and invalid attribute values.
     *
     * You give an array of valid and invalid values for each model 'active'
     * attribute and this method will verify that validation succeeds or fails
     * for these values.
     *
     * Validation data array format:
     *
     * - each root key must be a model attribute name and its value must be an
     * array - with 'valid' and 'invalid' keys - or FALSE - if you want to skip
     * verification for an attribute (see created_at);
     *
     * - 'valid' and 'invalid' keys must point to an array of values or FALSE -
     * if you want to skip verification for 'valid' or 'invalid' values (see
     * comment 'invalid' key);
     *
     * - a 'valid' or 'invalid' value can be a string, an integer or an array.
     * You can use the latter when you want to set other attributes values (see
     * passwordConfirm). In this case the array must contain a 'value' key
     * with the valid or invalid attribute value and an 'otherAttributes' key
     * with an array containing attributes name and value pairs;
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
     *          // password value must be set to verify confirmation
     *          'valid' => [
     *              // use an array to set attribute value and other attributes values
     *              // both passwordConfirm and password attributes receive the same value
     *              ['value' => 'validPassword', 'otherAttributes' => ['password' => 'validPassword']
     *          ]
     *          'invalid' => [
     *              // invalid value as a string
     *              'noPasswordSet',
     *              // invalid value as an array
     *              ['value' => 'onePassword', 'otherAttributes' => ['password' => 'otherPassword']]
     *          ]
     *      ],
     *      // skip verification with invalid comment values
     *      'comment' => ['valid' => ['Valid comment'], 'invalid' => FALSE],
     *      // skip verification for created_at attribute
     *      'created_at' => false,
     * ];
     * </code>
     *
     * @param array $validationData
     * @throws InvalidParamException
     */
    public function assertValidationDataMatchesValidationRules(array $validationData)
    {
        $model = clone $this->getModel();
        $errorMessages = '';

        // each 'active' attribute will be checked...
        foreach ($model->activeAttributes() as $attribute) {

            // ...unless you explicitely don't want to
            if (!array_key_exists($attribute, $validationData)) {
                throw new InvalidParamException("Missing validation data for attribute '$attribute'.
                    To skip verification for this attribute set its value to FALSE in validation data.");
            }
            if (false === $validationData[$attribute]) continue;

            // we expect validation data for an attribute to be an array with
            // 'valid' and 'invalid' keys
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
                    if (!$this->attributeValueIsValid($attribute, $value, $otherAttributes, false)) {
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
                    if ($this->attributeValueIsValid($attribute, $value, $otherAttributes, false)) {
                        $errorMessages .= "\nValidation should fail for attribute '$attribute' with value $value.";
                    }
                }
            }
        }
        self::assertTrue('' === $errorMessages, $errorMessages);
    }

    /**
     * Verify that an attribute validation fails with a non unique value.
     *
     * @param string $attribute attribute name
     */
    public function assertAttributeMustHaveUniqueValue($attribute)
    {
        $model = clone $this->getModel();

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
     */
    public function assertSafeAttributesAre(array $attributes)
    {
        $this->assertAttributesAre('safe', $attributes);
    }

    /**
     * Verify that $attributes matches the model 'active' attribute names.
     *
     * @param array $attributes attribute names
     */
    public function assertActiveAttributesAre(array $attributes)
    {
        $this->assertAttributesAre('active', $attributes);
    }

    /**
     * Verify that $attributes matches the model 'required' attribute names.
     *
     * Required attributes are the ones that use the RequiredValidator.
     *
     * @param array $attributes attribute names
     */
    public function assertRequiredAttributesAre($attributes)
    {
        $this->assertAttributesAre('required', $attributes);
    }

    /**
     * Verify that attributes are of type $type - internal use.
     *
     * @param string $type the expected type ('safe', 'active', 'required') of the attributes
     * @param array $attributes attribute names
     * @throws InvalidParamException param $type is not supported
     */
    protected function assertAttributesAre($type, array $attributes)
    {
        $model = clone $this->getModel();
        $assertionMessages = '';

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
            $assertionMessages .= "Attribute(s) [" . implode(', ', array_values($foundNotExpected)) . "] should not be $type in the model.\n";
        }

        if ([] !== $expectedNotFound) {
            $assertionMessages .= "Attribute(s) [" . implode(', ', array_values($expectedNotFound)) . "] should be $type in the model.\n";
        }

        $this->assertTrue('' === $assertionMessages, $assertionMessages);
    }

    /**
     * Return the 'required' 'active' attributes of the model.
     *
     * @return array
     */
    protected function getRequiredActiveAttributes()
    {
        $model = clone $this->getModel();
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
