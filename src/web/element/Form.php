<?php
namespace pyd\testkit\web\element;

use pyd\testkit\AssertionMessage;
use pyd\testkit\web\element\Helper as ElementHelper;

/**
 * A <form> element.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Form extends \pyd\testkit\web\Element
{
    /**
     * @var array input types for buttons
     */
    protected $buttonInputTypes = ['button', 'submit', 'reset', 'image'];


    protected function initLocators() {
        $this->addLocator('submitButton', \WebDriverBy::cssSelector('*[type="submit"]'));
        $this->addLocator('csrf', \WebDriverBy::name(\Yii::$app->getRequest()->csrfParam));
    }

    /**
     * The csrf element is present.
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
     * Find all user input elements i.e. <select> <textarea> and <input> which
     * are not buttons.
     *
     * @param null|bool $visible if null, it returns all elements. An element is
     * visible if {@link \pyd\testkit\web\Element::isDisplayed)(} returns true.
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
            $inputs = $this->findElements(\WebDriverBy::xpath($inputXpath), ['tagName' => 'input']);
            $this->userInputs = array_merge($textareas, $selects, $inputs);
        }

        if (true === $visible) return ElementHelper::removeHidden($this->userInputs);
        if (false === $visible) return ElementHelper::removeVisible($this->userInputs);
        return $this->userInputs;
    }

    /**
     * Reset user inputs.
     *
     * All previously found user inputs are cleared.
     */
    public function resetUserInputs()
    {
        $this->userInputs = null;
    }

    /**
     * Verify that a form contains expected named user inputs.
     *
     * @see findUserInputs
     *
     * @param array $names expected element names i.e. a model attribute e.g.
     * 'firstname' or a 'name' attribute value e.g. formName[firstname].
     * @param boolean $strict if false named element must exist in the form; If
     * true, named elements must match actual elements.
     */
    public function hasUserInputs($names, $displayed = true, $strict = true)
    {
        $actualInputs = $this->findUserInputs($displayed);
        // remove duplicate attribute names (in a radioButtonList, the same name can appear several times)
        $actualNames = \pyd\testkit\web\element\Helper::getNames($actualInputs, true);
        return $this->compareNameAttributes($actualNames, $names, $strict);
    }


    /**
     * Perform element name attributes comparison.
     *
     * @param array $actualNames actual element names in the yii format:
     * $formName[$attributeName]
     * @param array $expectedNames expected element names:
     * - model attribute e.g. 'password';
     * - element attribute e.g. 'user[password]';
     *
     * @param boolean $strict if false expected names must be found. If true
     * expected names must match actual names.
     *
     * @return boolean
     */
    public function compareNameAttributes(array $actualNames, array $expectedNames, $strict = true)
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
     *
     * @todo clean
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
    public function submitAndWaitForNewPage($timeout = 5, $interval = 200)
    {
        // add 'flag' element
        $this->execute(\DriverCommand::EXECUTE_SCRIPT, [
            'script' => 'var waitE = document.createElement("p");waitE.setAttribute("id", "submit-wait");document.body.appendChild(waitE);',
            'args' => []
        ]);
        // submit the form
       $this->submit();
       // wait until the flag element is not present anymore i.e. a new page is loaded
        $this->webDriver->wait($timeout, $interval)->until(
            function(){
                return null === func_get_arg(0)->executeScript('return document.getElementById("submit-wait");');
            },
            "$timeout seconds after submit, new page still not loaded."
        );
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
    public function addModelAttributesLocators(\yii\base\Model $model)
    {
        foreach ($model->attributes() as $attribute) {
            $id = \yii\helpers\Html::getInputId($model, $attribute);
            $by = \WebDriverBy::id($id);
            if (!$this->hasLocator($attribute)) {
                $alias = $attribute;
            } else {
                $alias = $model->formName() . '-' . $attribute;
            }
            $this->addLocator($alias, $by, false);
        }
    }
}
