<?php
namespace pyd\testkit\web;

/**
 * Send http request iva GET or POST.
 *
 * Target url is based on @see $route and generated using the urlManager component.
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
     * @var \pyd\testkit\web\Driver
     */
    protected $webDriver;

    public function __construct(Driver $webDriver, $config = array())
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
        $this->webDriver->get($url);
    }

    /**
     * Send an http GET request and wait until the document.readyState property
     * of the requested page is 'complete'.
     *
     * @param array $urlParams the url params ['paramName' => $paramValue, ...]
     */
    public function sendAndWaitReadyStateComplete(array $urlParams = [])
    {
        $this->webDriver->addPageFlag();
        $this->send($urlParams);
        $this->webDriver->waitReadyStateComplete();
    }

    /**
     * Send an http POST request and and wait until the document.readyState
     * property of the requested page is 'complete'.
     *
     * @see sendPost
     *
     * @param array $postData ['name' => 'value',...]
     * @param array $urlParams ['name' => 'value',...]
     * @param boolean|'auto' $addCsrf if true or false, the hidden field containing
     * the csrf token is appended or not to the form. If 'auto', the field is
     * appended according to the Request::$enableCsrfValidation property.
     * @param null|string $initialPageUrl navigate to this page to build the form
     * If null, the form will be build on the 'about' page.
     */
    public function sendPostAndWaitReadyStateComplete(array $postData = [], array $urlParams = [], $addCsrf = 'auto', $initialPageUrl = null)
    {
        if (null !== $initialPageUrl) {
            $this->webDriver->addPageFlag();
            $this->webDriver->get($initialPageUrl);
            $this->webDriver->waitReadyStateComplete();
        }

        $this->webDriver->addPageFlag();
        $this->addAndSubmitForm($postData, $urlParams, $addCsrf);
        $this->webDriver->waitReadyStateComplete();
    }

    /**
     * Send an http POST request.
     *
     * Selenium does not support sending POST request directly to the browser.
     * This method uses a common workaround:
     * - navigate to an initial page if needed;
     * - execute javascript to create a form (set POST data) and submit it;
     *
     * @see addAndSubmitForm
     *
     * @param array $postData ['name' => 'value',...]
     * @param array $urlParams ['name' => 'value',...]
     * @param boolean|'auto' $addCsrf if true or false, the hidden field containing
     * the csrf token is appended or not to the form. If 'auto', the field is
     * appended according to the Request::$enableCsrfValidation property.
     * @param null|string $initialPageUrl navigate to this page to build the form
     * If null, the form will be build on the 'about' page.
     */
    public function sendPost(array $postData = [], array $urlParams = [], $addCsrf = 'auto', $initialPageUrl = null)
    {
        if (null !== $initialPageUrl) {
            $this->webDriver->addPageFlag();
            $this->webDriver->get($initialPageUrl);
            $this->webDriver->waitReadyStateComplete();
        }

        $this->addAndSubmitForm($postData, $urlParams, $addCsrf);
    }

    /**
     * Execute javascript to create a form, add POST data and submit the form.
     *
     * @param array $postData ['name' => 'value',...]
     * @param array $urlParams ['name' => 'value',...]
     * @param boolean|'auto' $addCsrf if true or false, the hidden field containing
     * the csrf token is appended or not to the form. If 'auto', the field is
     * appended according to the Request::$enableCsrfValidation property.
     */
    protected function addAndSubmitForm(array $postData = [], array $urlParams = [], $addCsrf = 'auto')
    {
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

        $csrfParam = \Yii::$app->getRequest()->csrfParam;
        // hidden field for csrf token
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
     * Create an url using the urlManager component.
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
