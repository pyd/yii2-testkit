<?php
namespace pyd\testkit\web;

/**
 * WebDriver.
 * 
 * @todo could be usefull for this class to extend Object. \remoteWebDriver 
 * might be accessed via composition, maybe a __call?
 * 
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Driver extends \RemoteWebDriver
{
    /**
     * CSS id of a flag element.
     * @see waitNewPageStateComplete
     */
    const PAGE_FLAG_ID = 'page-flag-id';

    /**
     * @var \pyd\testkit\web\base\ElementFinder
     */
    private $_finder;

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
     * Search in the DOM for the first web element that matches the location
     * argument and return an object - default element class - representing it.
     *
     * If there's no matching, a @see \NoSuchElementException is raised.
     *
     * @param \WebDriverBy $by
     * @return \pyd\testkit\web\base\Element @see \pyd\testkit\web\base\ElementCreator::defaultType
     */
    public function findElement(\WebDriverBy $by)
    {
        return $this->findElementAs($by, null);
    }

    /**
     * Search in the DOM for all web elements that match the location argument
     * and return an array of objects - default element class - representing
     * them.
     *
     * If there's no matching, an empty array is returned.
     *
     * @param \WebDriverBy $by
     * @param string|array|callable $type @see \Yii::createObject
     * @return array of \pyd\testkit\web\base\Element @see \pyd\testkit\web\base\ElementCreator::defaultType
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
     * If there's no matching, a @see \NoSuchElementException is raised.
     *
     * @param \WebDriverBy $by
     * @param string|array|callable $type @see \Yii::createObject
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
     * @param string|array|callable $type @see \Yii::createObject
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

    private $pageHasFlag = false;
    /**
     * Add a flag - a web element - to the current page to detect when a new
     * page - element is not present anymore - is loading.
     *
     * @see waitNewPageStateComplete
     */
    public function addPageFlag()
    {
        $this->executeScript('var flag = document.createElement("span");
            flag.style.display="none";
            flag.id = "' . self::PAGE_FLAG_ID . '";
            document.body.appendChild(flag);'
        );
        $this->pageHasFlag = true;
    }

    /**
     * Wait until a new page is loading and its document.readyState is 'complete'.
     *
     * Note that the @see addPageFlag method must be executed before the http
     * request to detect new page.
     * <code>
     * $webDriver->addPageFlag();
     * // send a http request e.g. by clicking on a link
     * $webDriver->waitNewPageStateComplete();
     * </code>
     *
     * You don't have to use this with @see \pyd\testkit\web\base\Page::load().
     * It is implemented by the @see \pyd\testkit\web\request::sendAndWait()
     *
     * @todo back to initial script. Modification iwas done to verify why exactly documentElement check is needed
     *
     * @param integer $timeoutInSec stop waiting (and \TimeOutException) after
     * $timeoutInSec seconds.
     * @param integer $intervalInMillisec check readyState every $intervalInMillisec milliseconds (1/1000000 sec)
     * @throws \yii\base\InvalidCallException js variable was not added to the previous page
     */
    public function waitNewPageStateComplete ($timeoutInSec = 5, $intervalInMillisec = 400)
    {
        if (false === $this->pageHasFlag) {
            throw new \yii\base\InvalidCallException("The " . get_class() . "::addPageFlag()
                method must be executed prior to " . __METHOD__ . '.');
        }

        // If documentElement is null
        $this->wait($timeoutInSec, $intervalInMillisec)->until(function(){
//            $state = $this->executeScript("if (document.getElementById('" . self::PAGE_FLAG_ID . "') === null && 'complete' === document.readyState) { return 1; } else { return 0; }");
            $state = $this->executeScript("if ((document.documentElement !== null) && (document.getElementById('" . self::PAGE_FLAG_ID . "') === null) && ('complete' === document.readyState)) { return 1; } else { return 0; }");
            return (1 === $state);
        }, "document.readyState still not 'complete' after $timeoutInSec seconds.");

        $this->pageHasFlag = false;
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
}
