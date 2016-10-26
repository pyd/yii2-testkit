<?php
namespace pyd\testkit\web\Element;

/**
 * A <form> element.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Form extends \pyd\testkit\web\Element
{
    public $fieldsLocator = ['xpath', '//div[contains(@class, "form-group") and contains(@class, "field-")]'];


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

    private $_fields;

    /**
     * Return user input containers - field elements.
     *
     * @param string|array $reference
     * @return array
     */
    public function getFields($reference = '\pyd\testkit\web\Element')
    {
        if (null === $this->_fields) {
            $this->_fields = $this->findElements($this->fieldsLocator, $reference);
        }
        return $this->_fields;
    }

    private $_models;

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
     * Submit the form.
     */
    public function submit($wait = 0)
    {
        $this->execute(\DriverCommand::SUBMIT_ELEMENT);
        if ($wait > 0) {
            $page = new \pyd\testkit\web\Page($this->webDriver);
            $page->waitLoadComplete();
        }
    }

    /**
     * Submit the form and wait for the browser to load a new page.
     *
     * Append an element to the document body and wait untill it's not present.
     */
//    public function submitAndWait($timeout = 5, $interval = 200)
//    {
//
//        $this->execute(\DriverCommand::EXECUTE_SCRIPT, [
//            'script' => 'var waitE = document.createElement("p");waitE.setAttribute("id", "submit-wait");document.body.appendChild(waitE);',
//            'args' => []
//        ]);
//
//        $this->execute(\DriverCommand::SUBMIT_ELEMENT);
//
//        $this->webDriver->wait($timeout, $interval)->until(
//            function(){
//                return null === func_get_arg(0)->executeScript('return document.getElementById("submit-wait");');
//            },
//            "$timeout seconds after submit, new page still not loaded."
//        );
//        return $this;
//    }

    public function addModelAttributesLocators(\yii\base\Model $model)
    {
        $attributes = array_keys($model->getAttributes());
        foreach ($attributes as $attribute) {
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
