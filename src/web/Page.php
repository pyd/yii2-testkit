<?php
namespace pyd\testkit\web;

use pyd\testkit\AssertionMessage;

/**
 * A specific page.
 *
 * Usage:
 * <code>
 * class LoginPage extends \pyd\testkit\web\Page
 * {
 *      protected $route = 'users/auth/login';
 *
 *      protected $referenceLocation = 'loginForm';     // location is an alias, it could be an array
 *
 *      protected function initLocators()
 *      {
 *          $this->locator->add('loginForm', \WebDriverBy::id('login-form'));
 *          $this->locator->add('loginFailureMessage', \WebDriverBy::className('login-failure-message'));
 *      }
 *
 *      public function findLoginForm()
 *      {
 *          $form = $this->findElement('loginForm', \pyd\testkit\web\elements\Form::className());
 *          $form->addInputLocatorsByModel(new Login());
 *          return $form;
 *      }
 * }
 * $loginPage = new LoginPage($webDriver);
 * </code>
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Page extends base\Page
{
    /**
     * @var string url route
     */
    protected $route;
    /**
     * @var string|array|\WebDriverBy location of the reference element used
     * to verify if the expected page is displayed
     * @see isDisplayed()
     */
    protected $referenceLocation;
    /**
     * @var \pyd\testkit\web\Request
     */
    private $_request;

    /**
     * If $name is a location alias, a web element object will be returned.
     *
     * @param string $name a location alias
     * @return \pyd\testkit\web\base\Element
     */
    public function __get($name)
    {
        if ($this->locator->aliasExists($name)) {
            return $this->findElement($this->locator->get($name));
        }
        throw new \yii\base\UnknownPropertyException("Getting unknown property " .get_class(). "::$name.");
    }

    /**
     * @return \pyd\testkit\web\Request
     */
    public function getRequest()
    {
        if (null === $this->_request) {
            $this->setRequest(new Request($this->driver, ['route' => $this->route]));
        }
        return $this->_request;
    }

    public function setRequest(Request $request)
    {
        $this->_request = $request;
    }

    /**
     * @return string @see route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Load this page.
     *
     * @param array $urlParams
     * @param boolean $verifyIsDisplayed verify that the browser displays
     * this page @see isDisplayed
     * @throws InvalidCallException route was not initialized
     */
    public function load(array $urlParams = [], $verifyIsDisplayed = true)
    {
        if (null === $this->route) {
            throw new InvalidCallException("Property " . get_class($this) . "::\$route must be initialized to load the page.");
        }

        $this->getRequest()->sendAndWait($urlParams);

        if ($verifyIsDisplayed && !$this->isDisplayed()) {
            throw new PageIsNotDisplayedException('Browser does not display the expected page ' . get_class($this) . '.');
        }
    }

    /**
     * Verify that this page is displayed i.e. reference element is present.
     *
     * @return boolean
     * @throws InvalidCallException
     */
    public function isDisplayed()
    {
        if (null === $this->referenceLocation) {
            throw new InvalidCallException('You must define ' . get_class($this)
                    . '::$referenceLocation property.' );
        }

        if ($this->hasElement($this->referenceLocation)) {
            AssertionMessage::set('Page ' . get_class($this) . ' is displayed.');
            return true;
        } else {
            AssertionMessage::set('Page '  . get_class($this) . ' is not displayed.');
            return false;
        }
    }
}
