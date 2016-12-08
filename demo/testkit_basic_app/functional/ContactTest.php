<?php
namespace tests\functional;

/**
 * 'Contact' page tests.
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class ContactTest extends WebTestCase {

    /**
     * @return \tests\functional\ContactPage
     */
    public function getPage()
    {
        return new pages\ContactPage();
    }

    public function testPageContent()
    {
        $page = $this->getPage();
        $page->open();

        $breadcrumb = $page->getBreadcrumbContainer();
        $this->assertTrue($breadcrumb->isDisplayed());
        $this->assertEquals('Contact', $breadcrumb->getActiveLabelText());
        $this->assertTrue($page->form->isDisplayed());
    }

    public function testFormContent()
    {
        $page = $this->getPage();
        $page->open();

        $form = $page->getFormAsContainer();
        $this->assertTrue($form->hasCsrf());
        $this->assertTrue($form->hasInputs(['name', 'email', 'subject', 'body', 'verifyCode']));
        $this->assertTrue($form->hasElement('submitButton'));
    }

    public function testFormValidation()
    {
        $page = $this->getPage();
        $page->open();

        // see facebook RemoteWebElement::submit()
        $page->form->submit();                      // empty fields

        $form = $page->getFormAsContainer();
        $errors = $form->errors;                    // error message elements
        $this->assertCount(5, $errors);
    }

    public function testMessageSent()
    {
        $page = $this->getPage();
        $page->open();

        $form = $page->getFormAsContainer();
        $form->name->sendKeys('John DOE');
        $form->email->sendKeys('john.doe@gmail.com');
        $form->subject->sendKeys('Message subject');
        $form->body->sendKeys('Message body');
        $form->verifyCode->sendKeys('testme');

        $form->submit();

        // when using
        # $page->waitLoadComplete();
        // the 'successMsg' element is sometimes not detected.
        // Waiting for a specific element to be present is the usual workaround.
        $page->waitElementPresent('successMsg');

        $this->assertTrue($page->successMsg->isDisplayed());
        $this->assertContains('Thank you for contacting us', $page->successMsg->getText());
        $this->assertFalse($page->hasElement('form'));

        $mailFiles = glob(\Yii::getAlias(\Yii::$app->mailer->fileTransportPath) . '/*.eml');
        sort($mailFiles);
        $lastMailContent = file_get_contents(end($mailFiles));
        $this->assertContains('Subject: Message subject', $lastMailContent);
        $this->assertContains('From: John DOE <john.doe@gmail.com>', $lastMailContent);
        $this->assertContains('Message body', $lastMailContent);
    }
}
