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
     * @param array $postData name => value pairs of data to put in $_POST
     * @param false|'auto' $csrf if false, the hidden field containing the csrf
     * token is not appended to the form. If 'auto', the field is appended if
     * Yii require it to be present.
     * @param string $initialPageRoute the route part of the url to the initial
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     * page i.e. the one where the form in created
     */
    public function sendPost($postData = [], $csrf = 'auto', $initialPageRoute = '/', array $urlParams = [])
    {
        // load the base page
        $initialPageUrl = $this->createUrl($initialPageRoute, $urlParams);
        $this->webDriver->execute(\DriverCommand::GET, ['url' => $initialPageUrl]);

        // build javascript to create and submit the form
        $csrfParam = \Yii::$app->getRequest()->csrfParam;
        // create code for hidden inputs containing post data
        $postInputsCode = '';
        foreach ($postData as $name => $value) {
            $varName = $name.'Input';
            $postInputsCode .= "
                    var $varName = document.createElement('input');
                    $varName.setAttribute('type', 'hidden');
                    $varName.setAttribute('name', '$name')
                    $varName.setAttribute('value', '$value');
                    form.appendChild($varName);";

        }
        // create code for hidden input containing csrf token
        $csrfInputCode = '';
        if ('auto' === $csrf) {
            $csrfInputCode .= "
                    var csrfParamMeta = document.getElementsByName('csrf-param');
                    var csrfTokenMeta = document.getElementsByName('csrf-token');
                    if (csrfTokenMeta.length > 0) {
                        var csrfInput = document.createElement('input');
                        csrfInput.setAttribute('type', 'hidden');
                        csrfInput.setAttribute('name', csrfParamMeta[0].getAttribute('content'));
                        csrfInput.setAttribute('value', csrfTokenMeta[0].getAttribute('content'));
                        form.appendChild(csrfInput);
                    }
                    ";
        }
        // create a form using the target url
        // if there's a meta named 'csrf-token', add it's content to a csrf input
        // add form and submit it
        $script = <<<END
(function () {
    var form = document.createElement("form");
    form.setAttribute('method',"post");
    form.setAttribute('action',"{$this->createUrl($this->route, $urlParams)}");

    $csrfInputCode

    $postInputsCode

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
