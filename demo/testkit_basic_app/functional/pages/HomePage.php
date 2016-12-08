<?php
namespace tests\functional\pages;

use pyd\testkit\functional\base\ElementContainerElement;

/**
 * 'Home' page object.
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class HomePage extends BasePage {

    protected $route = '';
    protected $refLocation = ['className', 'site-index'];

    public function init()
    {
        parent::init();

        // $page->jumbotron will return the web element with CSS class 'jumbotron'
        $this->locator->addLocation('jumbotron', 'className', 'jumbotron');

        // training: there is something wrong with this location definition
        // see facebook WebDriverBy class
        $this->locator->addLocation('documentationLink', 'partialLink', 'Documentation');
    }

    /**
     * jumbotron Element as a container.
     * Child locations must be relative. Element::findElement(s) methods will
     * search for a matching location in the source of the element itself.
     */
    public function getJumbotronAsContainer()
    {
        $jumbotron = new ElementContainerElement($this->jumbotron);
        $jumbotron->getLocator()->addLocation('title', 'tagName', 'h1');
        $jumbotron->getLocator()->addLocation('linkToYii', 'tagName', 'a');        // DELETE
        return $jumbotron;
    }

}
