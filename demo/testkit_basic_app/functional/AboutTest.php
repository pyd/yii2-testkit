<?php
namespace tests\functional;

use pyd\testkit\functional\base\UnknownLocationAliasException;

/**
 * 'About' page tests.
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AboutTest extends WebTestCase {

    /**
     * @return \tests\functional\pages\AboutPage
     */
    public function getPage() {
        return new pages\AboutPage();
    }

    /**
     * @group training
     */
    public function testRefLocation()
    {
        $page = $this->getPage();

        try {
            $page->open();
        } catch (\Exception $e) {
            $msg = "\n#3 see \\pyd\\teskit\\functional\\Page::\$refLocation.";
            $this->showTrainingMessage($msg, $e);
        }
    }

    public function testPageContent()
    {
        $page = $this->getPage();
        $page->open();

        $this->assertTrue($page->navbar->isDisplayed());
        $this->assertTrue($page->footer->isDisplayed());

        $this->assertContains($page->findElement(['tagName', 'code'])->getText(), '/var/www/html/yii2-basic/views/site/about.php');
    }

    /**
     * @group training
     */
    public function testBreadcrumb()
    {
        $page = $this->getPage();
        $page->open();

        $breadcrumb = new containers\Breadcrumb($page->breadcrumb);
        $this->assertTrue($breadcrumb->isDisplayed());
        $this->assertEquals(2, count($breadcrumb->labels));

        if (null === $breadcrumb->getActiveLabelText()) {
            $msg = "\n#4 you have to implement the Breadcrumb::getActiveLabelText() method";
            $this->showTrainingMessage($msg);
        }

        $this->assertEquals('About', $breadcrumb->getActiveLabelText());
    }
}
