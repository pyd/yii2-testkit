<?php
namespace pyd\testkit\web;

/**
 * An http request to be send via webdriver.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Request extends \yii\base\Object
{
    /**
     * @var string route part of the request url
     */
    protected $route;

    /**
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;

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
     * Initialization.
     *
     * The @see $route property must be initialized
     *
     * @throws \yii\base\InvalidConfigException @see $route is not initialized
     */
    public function init()
    {
        if (null === $this->route) {
            throw new \yii\base\InvalidConfigException("Property " . get_class()
                    . "::\$route is not initialized.");
        }
    }

    /**
     * @param string $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @return string route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Send an http GET request.
     *
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     */
    public function send(array $urlParams = [])
    {
        $this->webDriver->get($this->createUrl($urlParams));
    }

    /**
     * Send an http GET request and wait until the document.readyState property
     * of the new page is 'complete'.
     *
     * @see \pyd\testkit\web\Driver::waitNewPageStateComplete
     *
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     */
    public function sendAndWait(array $urlParams = [])
    {
        $this->webDriver->addPageFlag();
        $this->send($urlParams);
        $this->webDriver->waitNewPageStateComplete();
    }

    /**
     * Send a POST request.
     *
     * Selenium does not support sending POST request directly to the browser.
     * A workaround is to execute a JS script which add a form to the DOM and
     * submit it.
     *
     * @param array $postData ['name' => 'value',...]
     * @param array $urlParams ['name' => 'value',...]
     * @param null|string $csrfToken if null the csrf validation is not enabled.
     * If a string, it can be either the token value or the special string
     * 'auto'. If the latter, the token will be searched in the DOM of the
     * current page before sending the request.
     * @param string|null $initialPageUrl the url of a page to load before
     * sending the request
     */
    public function sendPost(array $postData = [], array $urlParams = [], $csrfToken = null, $initialPageUrl = null)
    {
        if (null !== $initialPageUrl) {
            $this->loadInitialPage($initialPageUrl);
        }

        $this->createFormAndSubmit($postData, $urlParams, $csrfToken);
    }

    /**
     * Send a POST request and and wait until the document.readyState
     * property of the new page is 'complete'.
     *
     * @param array $postData ['name' => 'value',...]
     * @param array $urlParams ['name' => 'value',...]
     * @param null|string $csrfToken if null the csrf validation is not enabled.
     * If a string, it can be either the token value or the special string
     * 'auto'. If the latter, the token will be searched in the DOM of the
     * current page before sending the request.
     */
    public function sendPostAndWait(array $postData = [], array $urlParams = [], $csrfToken = 'auto', $initialPageUrl = null)
    {
        if (null !== $initialPageUrl) {
            $this->loadInitialPage($initialPageUrl);
        }

        $this->webDriver->addPageFlag();
        $this->createFormAndSubmit($postData, $urlParams, $csrfToken);
        $this->webDriver->waitNewPageStateComplete();
    }

    /**
     * Create an url using the urlManager component.
     *
     * @see \yii\web\UrlManager::createUrl()
     *
     * @param array $params
     * @return string
     */
    public function createUrl(array $params = [])
    {
        array_unshift($params, $this->route);
        return \Yii::$app->urlManager->createUrl($params);
    }

    /**
     * Create a form in the DOM and submit it.
     *
     * This method executes a JS script that:
     * - creates a form element;
     * - creates a hidden input for each POST data to send;
     * - eventually creates a csrf input;
     * - add inputs to the form;
     * - append the form to the body;
     * - submit the form;
     *
     * @param array $postData ['name' => 'value',...] data to send via POST
     * @param array $urlParams ['name' => 'value',...] url parameters
     * @param null|string $csrfToken if null the csrf validation is not enabled.
     * If a string, it can be either the token value or the special string
     * 'auto'. If the latter, the token will be searched in the DOM of the
     * current page before sending the request.
     */
    protected function createFormAndSubmit(array $postData = [], array $urlParams = [], $csrfToken = 'auto')
    {
        $action = $this->createUrl($urlParams);

        // form
        $script = "var form = document.createElement(\"form\");
                   form.setAttribute(\"method\",\"post\");
                   form.setAttribute(\"action\",\"$action\");";

        // hidden inputs containing POST data
        foreach ($postData as $name => $value) {
            $var = $name.'Input';
            $script .= "
                    var $var = document.createElement(\"input\");
                    $var.setAttribute(\"type\", \"hidden\");
                    $var.setAttribute(\"name\", \"$name\")
                    $var.setAttribute(\"value\", \"$value\");
                    form.appendChild($var);";
        }

        // csrf hidden input
        if (is_string($csrfToken)) {

            // search for the csrf token value in the DOM
            if ('auto' === $csrfToken) {

                $csrf = new Csrf($this->webDriver);
                $csrfToken = $csrf->getToken();

                if (null === $csrfToken) {
                    throw new \yii\base\InvalidParamException("The \$csrfToken " .
                            " param is set to 'auto' but the csrf token value " .
                            " cannot be found in the current page.");
                }
            }

            // add a csrf hidden input to the form
            $csrfParam = \Yii::$app->getRequest()->csrfParam;
            $script .= "var csrfInput = document.createElement(\"input\");
                        csrfInput.setAttribute(\"type\", \"hidden\");
                        csrfInput.setAttribute(\"name\", \"$csrfParam\");
                        csrfInput.setAttribute(\"value\", \"$csrfToken\");
                        form.appendChild(csrfInput);";
        }

        // append form to body and submit
        $script .= "document.body.appendChild(form); form.submit();";

        $script = "(function(){" . $script . "})();";

        $this->webDriver->executeScript($script);
    }

    /**
     * Load the initial page i.e. before the request is sent.
     *
     * @param string $url
     */
    protected function loadInitialPage($url)
    {
        $this->webDriver->addPageFlag();
        $this->webDriver->get($url);
        $this->webDriver->waitNewPageStateComplete();
    }
}
