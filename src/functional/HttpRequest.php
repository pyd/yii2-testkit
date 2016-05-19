<?php
namespace pyd\testkit\functional;

/**
 * Http request.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class HttpRequest extends \yii\base\Object
{
    /**
     * @var string route of this request
     */
    public $route;
    /**
     * @var string default http method used to send this request
     * @see $supportedMethods
     * @see send()
     */
    protected $defaultMethod = 'get';
    /**
     * @var array http methods that this class can use to send a request
     */
    protected $supportedMethods = ['get', 'post'];
    /**
     * @var string route of the page from where the post request will be sent
     * @see sendAsPost()
     */
    protected $postRequestBasePageRoute = '/';

    /**
     * Send this http request using the default http method.
     *
     * @see $defaultMethod
     *
     * @param array $params the url params ['paramName' => $paramValue, ...]
     * @throws \LogicException
     */
    public function send(array $params = [])
    {
        switch ($this->defaultMethod) {
            case 'get':
                $this->sendAsGet($params);
                break;
            case 'post':
                $this->sendAsPost($params);
                break;
            default:
                throw new \LogicException("Property " . get_class($this) .
                        "::\$defaultMethod has an unsupported http method '" .$this->defaultMethod. "'.");
        }
    }

    /**
     * Send this http request using the 'GET' method.
     *
     * @param array $params the url params ['paramName' => $paramValue, ...]
     */
    public function sendAsGet(array $params = [])
    {
        $url = $this->createUrl($params);
        $this->webDriver->execute(\DriverCommand::GET, ['url' => $url]);
    }

    /**
     * Send this http request using the 'POST' method.
     *
     * Selenium does not support sending POST request directly to the browser.
     * This method use a common workaround:
     * - load a page in the browser;
     * - send js to the browser in order to create a form in the page;
     * - submit the form;
     *
     * @see $postRequestBasePageRoute
     *
     * @param array $params the url params ['paramName' => $paramValue, ...]
     */
    public function sendAsPost(array $params = [])
    {
        // load base page in the browser
        $basePageUrl = \Yii::$app->urlManager->createUrl([$this->postRequestBasePageRoute]);
        $this->webDriver->execute(\DriverCommand::GET, ['url' => $basePageUrl]);

        // create js that will build a form in the base page and submit it
        $csrfParam = \Yii::$app->getRequest()->csrfParam;
        // create a form using the target url
        // if page has a meta named 'csrf-token', add a csrf field to the form using the meta content
        // add form and submit it
        $script = <<<END
(function () {
    var form = document.createElement("form");
    form.setAttribute('method',"post");
    form.setAttribute('action',"$url");

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
     * Create an url for this http request.
     *
     * This method uses \Yii::$app->urlManager->createUrl().
     *
     * @param array $params
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    protected function createUrl(array $params = [])
    {
        if (null === $this->route) {
            throw new \yii\base\InvalidConfigException("You must initialize " . get_class($this) . '::$route.');
        }
        array_unshift($params, $this->route);
        return \Yii::$app->urlManager->createUrl($params);
    }

    /**
     * Setter @see $defaultMethod.
     *
     * @param string $method http method name
     * @throws \yii\base\InvalidParamException unsupported method name
     */
    public function setDefaultMethod($method)
    {
        if (in_array($method, $this->supportedMethods)) {
            $this->defaultMethod = $method;
        } else {
            throw new \yii\base\InvalidParamException("Unsupported http method '$method'."
                    . " Supported methods are [" . implode(', ', $this->supportedMethods) . "].");
        }
    }
}
