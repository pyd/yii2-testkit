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
     * @var string route part of the request url
     */
    public $route;
    /**
     * @var \RemoteWebDriver
     */
    protected $webDriver;

    public function __construct(\RemoteWebDriver $webDriver, $config = array())
    {
        $this->webDriver = $webDriver;
        parent::__construct($config);
    }

    /**
     * Send an http request using the default 'GET' method.
     *
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     */
    public function send(array $urlParams = [])
    {
        $url = $this->createUrl($this->route, $urlParams);
        $this->webDriver->execute(\DriverCommand::GET, ['url' => $url]);
    }

    /**
     * Send an http request using the 'POST' method.
     *
     * Selenium does not support sending POST request directly to the browser.
     * This method uses a common workaround:
     * - load a page in the browser;
     * - execute js within the page to create a form;
     * - submit the form;
     *
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     * @param string $initialPageRoute the route part of the url to the initial
     * page i.e. the one where the form in created
     */
    public function sendPost(array $urlParams = [], $initialPageRoute = '/')
    {
        // load the base page
        $initialPageUrl = $this->createUrl($initialPageRoute, $urlParams);
        $this->webDriver->execute(\DriverCommand::GET, ['url' => $initialPageUrl]);

        // create the form
        $csrfParam = \Yii::$app->getRequest()->csrfParam;
        $formAction = $this->createUrl($this->route, $urlParams);
        // create a form using the target url
        // if there's a meta named 'csrf-token', add it's content to a csrf input
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
        $this->webDriver->executeScript($script);
    }

    /**
     * Create an url using the Yii app url manager.
     *
     * @param string $route
     * @param array $urlParams
     * @return string
     */
    public function createUrl($route, array $urlParams = [])
    {
        array_unshift($urlParams, $route);
        return \Yii::$app->urlManager->createUrl($urlParams);
    }
}
