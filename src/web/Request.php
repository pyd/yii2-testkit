<?php
namespace pyd\testkit\web;

/**
 * Http request.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Request extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;
    /**
     * @var string route of the page from where the post request will be sent
     * @see sendViaPost()
     */
    protected $postBaseRoute = '/';

    /**
     * @param \pyd\testkit\web\Driver $webDriver
     * @param array $config
     */
    public function __construct(Driver $webDriver, $config = array())
    {
        $this->webDriver = $webDriver;
        parent::__construct($config);
    }

    /**
     * Setter for @see $postBaseRoute.
     *
     * @param string $route
     */
    public function setPostBaseRoute($route)
    {
        $this->postBaseRoute = $route;
    }

    /**
     * Send an http request using the 'GET' method.
     *
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     */
    public function sendViaGet($route, array $urlParams = [])
    {
        $url = self::createUrl($route, $urlParams);
        $this->webDriver->execute(\DriverCommand::GET, ['url' => $url]);
    }

    /**
     * Send an http request using the 'POST' method.
     *
     * Selenium does not support sending POST request directly to the browser.
     * This method use a common workaround:
     * - load a page in the browser;
     * - send js to the browser in order to create a form in the page;
     * - submit the form;
     *
     * @see $postBaseRoute
     *
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     */
    public function sendViaPost($route, array $urlParams = [])
    {
        // load base page in the browser
        $this->sendViaGet($this->postBaseRoute);

        // create js that will build a form in the base page and submit it
        $csrfParam = \Yii::$app->getRequest()->csrfParam;
        $formAction = self::createUrl($route, $urlParams);
        // create a form using the target url
        // if page has a meta named 'csrf-token', add a csrf field to the form using the meta content
        // add form and submit it
        $script = <<<END
(function () {
    var form = document.createElement("form");
    form.setAttribute('method',"post");
    form.setAttribute('action',"$formAction");

    var csrfMeta = document.getElementsByName('csrf-token');
    if (csrfMeta.length > 0) {
        var input = document.createElement("input");
        input.setAttribute('type', "hidden");
        input.setAttribute('name', "$csrfParam");
        input.setAttribute('value', csrfMeta[0].getAttribute('content'));
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
})();
END;
        // send and execute js within the base page
        $this->webDriver->executeScript($script);
    }

    /**
     * Create an url.
     *
     * @param string $route
     * @param array $urlParams
     * @return string url
     */
    public static function createUrl($route, array $urlParams = [])
    {
        array_unshift($urlParams, $route);
        return \Yii::$app->urlManager->createUrl($urlParams);
    }
}
