<?php
namespace tests\functional\pages;

/**
 * 'Contact' page object.
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class ContactPage extends BasePage {

    protected $route = 'site/contact';

    protected $refLocation = ['className', 'site-contact'];

    public function init()
    {
        parent::init();

        $this->locator->addLocation('form', 'id', 'contact-form');
        $this->locator->addLocation('successMsg', 'cssSelector', '.site-contact .alert-success');
    }

    /**
     * @return \pyd\testkit\web\element\Form
     */
    public function getFormAsContainer()
    {
        $form = new \pyd\testkit\web\element\Form($this->form);
        $form->addModelAttributesLocators(new \app\models\ContactForm());
        $form->getLocator()->addLocation('errors', 'className', 'help-block-error');
        return $form;
    }
}
