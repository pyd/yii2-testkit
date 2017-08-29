<?php
namespace pyd\testkit\web\elements;

use pyd\testkit\AssertionMessage;
use pyd\testkit\web\elements\Helper as ElementHelper;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;

/**
 * A <form> element created by the yii\widgets\ActiveForm widget.
 *
 * @property \pyd\testkit\web\base\Element $submitButton
 * @property \pyd\testkit\web\base\Element $csrf csrf hidden input
 * @property array of \pyd\testkit\web\base\Element $helpBlockError validation
 * error msg containers
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Form extends \pyd\testkit\web\Element
{
    /**
     * @var array input types for buttons
     * @see findUserInputs
     */
    protected $buttonInputTypes = ['button', 'submit', 'reset', 'image'];

    /**
     * Add input locators for model attributes.
     *
     * This method will create locators to find inputs using their attribute names.
     *
     * <code>
     * // assuming the Users model has safe 'username' and 'country_id' attributes
     * $model = new \app\model\Users;
     * $form->addInputLocatorsByModel($model);
     * ...
     * $form->username->setAttribute('value', 'tarzan');     // write 'tarzan' in the 'username' text field
     * $form->find('country_id', Select::className)->selectByValue('Vanuatu');
     * </code>
     *
     * @param \yii\base\Model $model
     * @param array $attributes a locator will be added for each of this
     * attributes. If empty, attributes returned by Model::safeAttributes() will
     * be used.
     */
    public function addInputLocatorsByModel(\yii\base\Model $model, array $attributes = [])
    {
        if ([] === $attributes) $attributes = $model->safeAttributes();
        foreach ($attributes as $attribute) {
            $locatorAlias = (!$this->locator->aliasExists($attribute)) ? $attribute : $model->formName() . '-' . $attribute;
            $cssId = \yii\helpers\Html::getInputId($model, $attribute);
            $this->locator->add($locatorAlias, \WebDriverBy::id($cssId), false);
        }
    }

    /**
     * Verify if an input has an invalid value.
     *
     * Note that input locator must have been added for this attribute with
     * @see addInputLocatorsByModel
     *
     * @param string $attribute
     * @return boolean
     * @throws InvalidCallException $attribute locator does not use the 'id' mechanism
     * @throws InvalidParamException no locator was defined for $attribute
     */
    public function inputHasError($attribute)
    {
        if ($this->locator->aliasExists($attribute)) {
            $by = $this->locator->resolve($attribute);
            if ('id' === $by->getMechanism()) {
                try {
                    $cssSelector = '.field-' . $by->getValue() . '.has-error';
                    $this->finder->getChildID(\WebDriverBy::cssSelector($cssSelector), $this->getID());
                    AssertionMessage::set("Attribute '$attribute' has validation error.");
                    return true;
                } catch (\NoSuchElementException $e) {
                    AssertionMessage::set("No validation error for attribute '$attribute'.");
                    return false;
                }
            }else {
                throw new InvalidCallException("Attribute '$attribute' location
                    must use the 'id' mechanism.");
            }

        } else {
            throw new InvalidParamException("No location was defined for
                '$attribute' input.");
        }
    }

    protected function initLocators() {
        $this->locator->add('submitButton', \WebDriverBy::cssSelector('*[type="submit"]'));
        $this->locator->add('csrf', \WebDriverBy::name(\Yii::$app->getRequest()->csrfParam));
        $this->locator->add('helpBlockError', \WebDriverBy::className('help-block-error'));
    }

    /**
     * The csrf hidden input is present.
     *
     * @return boolean
     */
    public function hasCsrf()
    {
        if($this->hasElement('csrf')) {
            AssertionMessage::set('A csrf element is present.');
            return true;
        } else {
            AssertionMessage::set('Csrf element is not present.');
            return false;
        }
    }

    private $userInputs;

    /**
     * Find all 'user input' elements i.e. <select> <textarea> and <input> (except
     * buttons @see $buttonInputTypes)
     *
     * @param bool|null $visible return 'displayed' or 'not displayed' elements
     * @see\pyd\testkit\web\Element::isDisplayed. If null all 'user inputs' are
     * returned.
     * @return array \pyd\testkit\web\Element
     */
    public function findUserInputs($visible = null)
    {
        if (null === $this->userInputs) {
            $textareas = $this->findElements(\WebDriverBy::tagName('textarea'));
            $selects = $this->findElements(\WebDriverBy::tagName('select'));

            $inputTypesToSkip = [];
            foreach ($this->buttonInputTypes as $type) {
                $inputTypesToSkip[] = "not(@type='$type')";
            }
            // ".//input//[not(@type='type1') and not(@type='type2' ...)]"
            $inputXpath = ".//input[".  implode(' and ', $inputTypesToSkip)."]";
            $inputs = $this->findElements(\WebDriverBy::xpath($inputXpath));
            $this->userInputs = array_merge($textareas, $selects, $inputs);
        }

        if (true === $visible) return ElementHelper::removeHidden($this->userInputs);
        if (false === $visible) return ElementHelper::removeVisible($this->userInputs);
        return $this->userInputs;
    }

    /**
     * Reset user inputs.
     */
    public function resetUserInputs()
    {
        $this->userInputs = null;
    }

    /**
     * Verify that a form contains expected user inputs.
     *
     * @see findUserInputs
     *
     * @param array $names expected element names i.e. a model attribute e.g.
     * 'firstname' or a 'name' attribute value e.g. formName[firstname].
     * @param boolean $visible search for visible or hidden inputs
     * @param boolean $strict if true, expected inputs must exactly match found
     * inputs. If false, form can have more inputs than the expected ones.
     */
    public function hasUserInputs($names, $visible = true, $strict = true)
    {
        $actualInputs = $this->findUserInputs($visible);
        // remove duplicate attribute names (in a radioButtonList, the same name can appear several times)
        $actualNames = \pyd\testkit\web\elements\Helper::getNames($actualInputs, true);
        return $this->compareNameAttributes($names, $actualNames, $strict);
    }


    /**
     * Compare element name attributes.
     *
     * @param array $expectedNames element names that should be present in the
     * $actualNames param. Name format is 'password'.
     * @param array $actualNames element names reference. Name format can be
     * 'password' or 'user[password]'.
     * @param boolean $strict if false expected names must be found. If true
     * expected names must match actual names.
     * @return boolean
     */
    public function compareNameAttributes(array $expectedNames, array $actualNames, $strict = true)
    {
        $this->verifyNoDuplicates($expectedNames);

        // each name that is 'expected' and 'actual' is removed from both lists
        foreach ($actualNames as $actualKey => $actualName) {

            $expectedNameKey = array_search($actualName, $expectedNames);

            // compare both names in element attribute format:
            // $expectedName = user[name] and $actualName = user[name]
            if (false !== $expectedNameKey) {
                unset($actualNames[$actualKey]);
                unset($expectedNames[$expectedNameKey]);
                continue;
            }

            foreach ($expectedNames as $expectedNameKey => $expectedName) {

                // compare expected names in short format:
                // $expectedName = password and $actualName = user[password]
                if (false !== strpos($actualName, "[$expectedName]")) {
                    unset($actualNames[$actualKey]);
                    unset($expectedNames[$expectedNameKey]);
                }
            }
        }

        AssertionMessage::clear();

        if ($strict) {
            foreach ($actualNames as $unexpectedName) {
                AssertionMessage::add("Form element $unexpectedName was not expected to be found.", true);
            }
        }

        foreach ($expectedNames as $notFoundName) {
            AssertionMessage::add("Expected form element $notFoundName was not found.", true);
        }

        return [] === $actualNames && [] === $expectedNames;
    }

    /**
     * Verify that an array does not contain duplicate items.
     *
     * @param array $items
     * @throws InvalidParamException
     */
    protected function verifyNoDuplicates(array $items)
    {
        $duplicates = array_diff_assoc($items, array_unique($items));
        if ([] !== $duplicates) {
            $duplicates = implode(', ', $duplicates);
            throw new InvalidParamException("Array contains duplicates [$duplicates].");
        }
    }

    /**
     * Submit the form using the selenium 'submit' command.
     */
    public function submit()
    {
        $this->execute(\DriverCommand::SUBMIT_ELEMENT);
    }

    /**
     * Submit the form and wait for the new page readyState to be 'complete'.
     */
    public function submitAndWaitNewPageComplete($timeout = 5, $interval = 200)
    {
        $this->driver->addPageFlag();
        $this->submit();
        $this->driver->waitNewPageStateComplete();
    }

    /**
     * Validation error messages are displayed in the form.
     *
     * @return boolean
     */
    public function hasValidationErrors()
    {
        $elements = $this->findElements('helpBlockError');
        $messages = [];
        foreach ($elements as $element) {
            $message = $element->getText();
            if (!empty($message)) {
                $messages[] = $message;
            }
        }
        if ([] === $messages) {
            AssertionMessage::set("No validation error message(s) displayed.");
            return false;
        } else  {
            AssertionMessage::set("Validation error message(s) displayed");
            foreach ($messages as $msg) {
                AssertionMessage::add($msg, true);
            }
            return true;
        }
    }

    /**
     * Find the first element matching $location and returns it as a text input.
     *
     * @param string|array|\WebDriverBy $location
     * @return \pyd\testkit\web\elements\TextInput
     */
    public function findTextInput($location)
    {
        return $this->findElement($location, TextInput::className());
    }

    /**
     * Find the first element matching $location and returns it as a checkbox.
     *
     * @param string|array|\WebDriverBy $location
     * @return \pyd\testkit\web\elements\Checkbox
     */
    public function findCheckbox($location)
    {
        return $this->findElement($location, Checkbox::className());
    }

    /**
     * Find the first element matching $location and returns it as a select.
     *
     * @param string|array|\WebDriverBy $location
     * @return \pyd\testkit\web\elements\Select
     */
    public function findSelect($location)
    {
        return $this->findElement($location, Select::className());
    }
}
