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
     * Send an http GET request.
     *
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     */
    public function send(array $urlParams = [])
    {
        $url = $this->createUrl($this->route, $urlParams);
        $this->webDriver->execute(\DriverCommand::GET, ['url' => $url]);
    }

    /**
     * Send an http POST request.
     *
     * Selenium does not support sending POST request directly to the browser.
     * This method uses a common workaround:
     * - load an 'initial' page in the browser;
     * - execute js within the page to create a form;
     * - submit the form;
     *
     * @param array $postParams params added to the POST body
     * @param array $getParams params added to the url of the form 'action'
     * @param boolean|'auto' $addCsrf if true or false, the hidden field containing
     * the csrf token is appended or not to the form. If 'auto', the field is
     * appended according to the Request::$enableCsrfValidation property.
     * @param string $initialPageUrl the url of the page where the form is added
     */
    public function sendPost(array $postParams = [], array $getParams = [], $addCsrf = 'auto', $initialPageUrl = null)
    {
        // load the initial page
        if (null === $initialPageUrl) $initialPageUrl = $this->createUrl ('/');
        $this->webDriver->execute(\DriverCommand::GET, ['url' => $initialPageUrl]);

        // build javascript to create and submit the form
        $csrfParam = \Yii::$app->getRequest()->csrfParam;
        // create code for hidden inputs containing post data
        $postInputsCode = '';
        foreach ($postParams as $name => $value) {
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
        if (('auto' === $addCsrf && \Yii::$app->getRequest()->enableCsrfValidation) || true === $addCsrf) {
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
    form.setAttribute('action',"{$this->createUrl($this->route, $getParams)}");

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
