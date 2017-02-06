<?php
namespace pyd\testkit\web\elements;

use pyd\testkit\AssertionMessage;
use pyd\testkit\web\elements\Helper as ElementHelper;

/**
 * A <form> element.
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
     * @var array to identify an <input> element that is a button. An <input>
     * element with another type is considered a 'user input'.
     * @see findUserInputs
     */
    protected $buttonInputTypes = ['button', 'submit', 'reset', 'image'];

    protected function initLocators() {
        $this->addLocator('submitButton', \WebDriverBy::cssSelector('*[type="submit"]'));
        $this->addLocator('csrf', \WebDriverBy::name(\Yii::$app->getRequest()->csrfParam));
        $this->addLocator('helpBlockError', \WebDriverBy::className('help-block-error'));
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
            $textareas = $this->findElements(\WebDriverBy::tagName('textarea'), ['tagName' => 'textarea']);
            $selects = $this->findElements(\WebDriverBy::tagName('select'), ['tagName' => 'select']);

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

    private $_models;

    /**
     * Define models - their attributes - used by the form and add their
     * attributes as locators.
     * @see addModelAttributesLocators()
     *
     * @param array \yii\base\models
     * @throws \yii\base\InvalidCallException
     */
    public function setModels(array $models)
    {
        if (null !== $this->_models) {
            throw new \yii\base\InvalidCallException("Models can only be initialized once."
                    . " You should create a new " . __METHOD__ . " instance.");
        }
        foreach ($models as $model) {
            $modelInstance = \yii\di\Instance::ensure($model, '\yii\base\Model');
            $this->_models[] = $modelInstance;
            $this->addModelAttributesLocators($modelInstance);
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
     * Submit the form and wait for the browser to load a new page.
     *
     * Append an element to the document body and wait untill it's not present.
     */
    public function submitAndWaitReadyStateComplete($timeout = 5, $interval = 200)
    {
        $this->webDriver->addPageFlag();
        $this->submit();
        $this->webDriver->waitReadyStateComplete();
    }

    /**
     * Add locators for the model attributes.
     *
     * If a 'user' model has 'firstname' and 'lastname' attributes this will has
     * a 'lastname' and a 'firstname' locators:
     * ```php
     * $form->firstname; // will return the user[firstname] text input element
     * $form->lastname;  // will return the user[lastname] text input element
     * ```
     *
     * The \yii\base\Model::getAttributes() method is used to retrieve the model
     * attribute names.
     */
    public function addModelAttributesLocators(\yii\base\Model $model, array $attributes = [])
    {
        if ([] === $attributes) $attributes = $model->attributes();
        foreach ($attributes as $attribute) {
            $locatorAlias = (!$this->hasLocator($attribute)) ? $attribute : $model->formName() . '-' . $attribute;
            $cssId = \yii\helpers\Html::getInputId($model, $attribute);
            $this->addLocator($locatorAlias, \WebDriverBy::id($cssId), false);
        }
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
