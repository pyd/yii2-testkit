<?php
namespace tests\functional\pages;

/**
 * @brief ...
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class LoginPage extends BasePage {

    protected $route = 'site/login';

    protected $refLocation = ['className', 'site-login'];

    public function init()
    {
        parent::init();

        // training: add form location
    }

    public function getFormAsContainer()
    {
        // create an container with form as element: ElementContainer::getElementAsContainer()
        // add model attribute locations: Form::addModelAttibuteLocations()
        // add error message locations ['className', 'help-block-error'] for this form
    }
}
