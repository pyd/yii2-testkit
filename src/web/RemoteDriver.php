<?php
namespace pyd\testkit\web;

use WebDriverExpectedCondition;

/**
 * Remote Web Driver.
 * 
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class RemoteDriver extends \RemoteWebDriver
{
    /**
     * CSS id of a flag element.
     * @see addPageFlag()
     * @see waitNewPageStateComplete()
     */
    const PAGE_FLAG_ID = 'page-flag-id';
    
    /**
     * @var \pyd\testkit\web\base\ElementFinder
     */
    private $_finder;
    
    /**
     * @see addPageFlag()
     * @see waitNewPageStateComplete()
     * @var boolean the 'flag' element has been added to a page
     */
    private $pageFlagAdded = false;
    
    /**
     * @return \pyd\testkit\web\base\ElementFinder
     */
    public function getElementFinder()
    {
        if (null === $this->_finder) {
            $this->_finder = new base\ElementFinder($this->getExecuteMethod(), new base\ElementCreator($this));
        }
        return $this->_finder;
    }

    /**
     * Search in the DOM for the first web element that matches the $by
     * argument and return it.
     * 
     * This method override parent's implementation because the it's returning
     * an instance of {@see \RemoteWebElement}.
     *
     * If no matching element was found, a {@see \NoSuchElementException} is
     * raised.
     *
     * @param \WebDriverBy $by location
     * @return \pyd\testkit\web\base\Element
     */
    public function findElement(\WebDriverBy $by)
    {
        return $this->findElementAs($by, null);
    }

    /**
     * Search in the DOM for all web elements that match the $by argument
     * and return them as an array.
     *
     * This method override parent's implementation because the it's returning
     * an array of instances of {@see \RemoteWebElement}.
     * 
     * If no matching element was found, an empty array is returned.
     *
     * @param \WebDriverBy $by location
     * @param string|array|callable $type {@see \Yii::createObject()}
     * @return array of \pyd\testkit\web\base\Element {@see \pyd\testkit\web\base\ElementCreator::defaultType}
     */
    public function findElements(\WebDriverBy $by)
    {
        return $this->findElementsAs($by, null);
    }

    /**
     * Search in the DOM for the first web element that matches the location
     * argument and return an object - created using $type param - representing
     * it.
     *
     * If there's no matching, a @see {\NoSuchElementException} is raised.
     *
     * @param \WebDriverBy $by
     * @param string|array|callable $type {@see \Yii::createObject()}
     * @return \pyd\testkit\web\base\Element subclass
     */
    public function findElementAs(\WebDriverBy $by, $type)
    {
        return $this->getElementFinder()->findElement($by, $type);
    }

    /**
     * Search in the DOM for all web elements that match the location argument
     * and return an array of objects - created using $type param - representing
     * them.
     *
     * If there's no matching, an empty array is returned.
     *
     * @param \WebDriverBy $by
     * @param string|array|callable $type {@see \Yii::createObject()}
     * @return array
     */
    public function findElementsAs(\WebDriverBy $by, $type)
    {
        return $this->getElementFinder()->findElements($by, $type);
    }

    /**
     * Check if a web element is present in the DOM (visible or not).
     *
     * @param \WebDriverBy $by
     * @return boolean
     */
    public function hasElement(\WebDriverBy $by)
    {
        return $this->getElementFinder()->hasElement($by);
    }

    /**
     * @return \pyd\testkit\functional\base\Cookies the cookies manager
     */
    public function cookies()
    {
        return new Cookies($this->getExecuteMethod());
    }

    /**
     * Add a 'flag' web element to the current page.
     * 
     * The 'flag' is a <span> element with unique css. It is used to wait for a
     * new page to be loaded.
     * 
     * @see waitNewPageStateComplete()
     */
    public function addPageFlag()
    {
        $this->executeScript('var flag = document.createElement("span");
            flag.style.display="none";
            flag.id = "' . self::PAGE_FLAG_ID . '";
            document.body.appendChild(flag);'
        );
        $this->pageFlagAdded = true;
    }

    /**
     * Wait until a new page is loading and its document.readyState is 'complete'.
     *
     * Note that a 'flag' element must have been added to the DOM of the current
     * page {@see addPageFlag()} before sending the request.
     * <code>
     * $webDriver->addPageFlag();
     * /$request->send();
     * $webDriver->waitNewPageStateComplete();
     * </code>
     *
     * @param integer $timeoutInSec stop waiting (and \TimeOutException) after
     * $timeoutInSec seconds.
     * @param integer $intervalInMillisec check readyState every $intervalInMillisec milliseconds (1/1000000 sec)
     * @throws \yii\base\InvalidCallException js variable was not added to the previous page
     */
    public function waitNewPageStateComplete ($timeoutInSec = 5, $intervalInMillisec = 400)
    {
        if (false === $this->pageFlagAdded) {
            throw new \yii\base\InvalidCallException("A 'flag' element should have been added to the page."
                    . " Did you execute the" . get_class() . "::addPageFlag() method before sending the reqeust?");
        }

        $this->wait($timeoutInSec, $intervalInMillisec)
                ->until(function(){
            $state = $this->executeScript("if ((document.documentElement !== null) && (document.getElementById('" . self::PAGE_FLAG_ID . "') === null) && ('complete' === document.readyState)) { return 1; } else { return 0; }");
            return (1 === $state);
        }, "document.readyState still not 'complete' after $timeoutInSec seconds.");

        $this->pageFlagAdded = false;
    }

    /**
     * Get the element that has focus.
     *
     * @return \pyd\testkit\web\base\Element
     */
    public function findActiveElement()
    {
        return $this->getElementFinder()->findActiveElement();
    }
    
    /**
     * Waiting until a JS alert is displayed.
     * 
     * @param int $timeoutInSec
     * @param int $intervalInMillisec
     */
    public function waitAlertDisplayed($timeoutInSec = 3, $intervalInMillisec = 600)
    {
        $this->wait($timeoutInSec, $intervalInMillisec)->until(WebDriverExpectedCondition::alertIsPresent(), "No alert present after waiting $timeoutInSec seconds.");
    }
}
