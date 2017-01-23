<?php
namespace pyd\testkit\web;

/**
 * Custom web driver.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Driver extends \RemoteWebDriver
{
    /**
     * name of the javascript variable used as a flag
     * @see waitReadyStateComplete
     */
    const FLAG_ELEMENT_ID = 'previous-page-flag';
    /**
     * @var boolean the page flag element has been added to the DOM
     * @see addPageFlag
     */
    protected $pageHasFlag = false;

    /**
     * @return \pyd\testkit\functional\base\Cookies the cookies manager
     */
    public function cookies()
    {
        return new Cookies($this->getExecuteMethod());
    }

    /**
     * Add an hidden element to the page with a known ID to detect when the
     * browser has moved to another page.
     * @see waitReadyStateComplete
     */
    public function addPageFlag()
    {
        $this->executeScript('var span = document.createElement("span"); span.style.display="none"; span.id = "' . self::FLAG_ELEMENT_ID . '"; document.body.appendChild(span);');
        $this->pageHasFlag = true;
    }

    /**
     * Wait until the browser has moved to the new page (the page flag element
     * is no longer present) and its document.readyState property is 'complete'.
     *
     * @param integer $timeoutInSec stop waiting (and \TimeOutException) after
     * $timeoutInSec seconds.
     * @param integer $intervalInMillisec check readyState every $intervalInMillisec milliseconds (1/1000000 sec)
     * @throws \yii\base\InvalidCallException js variable was not added to the previous page
     */
    public function waitReadyStateComplete ($timeoutInSec = 5, $intervalInMillisec = 400)
    {
        if (false === $this->pageHasFlag) {
            throw new \yii\base\InvalidCallException("Cannot wait for 'complete' readyState. The page"
                    . " should have been tagged before the http request with the addPageFlag method.");
        }

        $this->wait($timeoutInSec, $intervalInMillisec)->until(function(){
            $state = $this->executeScript("if ((document.documentElement !== null) && (document.getElementById('" . self::FLAG_ELEMENT_ID . "') === null) && ('complete' === document.readyState)) { return 1; } else { return 0; }");
            return (1 === $state);
        }, "document.readyState still not 'complete' after $timeoutInSec seconds.");

        $this->pageHasFlag = false;
    }
}
