<?php
namespace tests\functional;

use pyd\testkit\functional\base\ElementContainerElement;
use pyd\testkit\functional\base\UnknownLocationAliasException;
use yii\base\InvalidParamException;
use yii\base\UnknownPropertyException;

/**
 * 'Home' page tests.
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class HomeTest extends WebTestCase {

    /**
     * @return \tests\functional\pages\HomePage
     */
    public function getPage()
    {
        return new pages\HomePage();
    }

    /**
     * @group training
     */
    public function testLocationMechanism()
    {
        try {

            $page = $this->getPage();
            $page->open();
            $this->assertTrue($page->documentationLink->isDisplayed());

        } catch (InvalidParamException $e) {
            $msg = "\n#1: fix this invalid mechanism in HomePage.php";
            $this->showTrainingMessage($msg, $e);
        }

    }

    /**
     * No training here, just demo
     */
    public function testPageContent()
    {
        $page = $this->getPage();
        // open(array $params = [], $checkDisplay = true)
        // $page->isDisplayed() is called by default
        // if reference element (HomePage::$refLocation) is not present
        // an exception will be thrown
        $page->open();

        // element is present in the DOM & visible
        $this->assertTrue($page->navbar->isDisplayed());

        // we know that the jumbotron element is present in the DOM
        // otherwise a facebook NoSuchElementException would have been thrown.
        // It can be visible or not
        $jumbotron = $page->jumbotron;

        // now we know that jumbotron is visible
        $this->assertTrue($jumbotron->isDisplayed());

        // getText = get content of an element - including html
        // does not work with input fields: use $field->getAttribute('value') instead
        $this->assertContains('Congratulations', $jumbotron->getText());

        $this->assertFalse($page->hasElement('breadcrumb'));                    // not present in the DOM
    }

    /**
     * @group training
     */
    public function testDOMLocation()
    {
        $page = $this->getPage();
        $page->open();

        try {
            $this->assertTrue($page->footer->isDisplayed());
        } catch (UnknownPropertyException $e) {
            $msg = "\n#2: add a location for the footer with alias 'footer' in BasePage.php";
            $this->showTrainingMessage($msg, $e);
        }
    }

    /**
     * @group training
     */
    public function testRelativeLocation()
    {
        $page = $this->getPage();
        $page->open();

        // NoSuchElementException if the 'jumbotron' element is not present in the DOM
        $jumbotron = $page->getJumbotronAsContainer();

        $this->assertTrue($jumbotron->isDisplayed());

        // $element->getText => get the source between opening and closing tags - including html sub tags ...
        // $inputField->getText() or $textarea->getText() won't work
        // you have to use $inputField->getAttribute('value') instead
        $this->assertContains('Congratulations', $jumbotron->title->getText());

        try {
            $this->assertTrue($jumbotron->linkToYii->isDisplayed());

        } catch (UnknownLocationAliasException $e) {
            $msg = "\n#3 add a location for the big green button";
            $msg .= "\n Name it 'linkToYii'. Don't forget this is a jumbotron child element.";
            $this->showTrainingMessage($msg, $e);
        }
    }

}
