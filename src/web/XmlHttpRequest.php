<?php
namespace pyd\testkit\web;

use pyd\testkit\web\RemoteDriver;
use yii\helpers\Url;
use yii\base\InvalidParamException;
use yii\base\InvalidCallException;
use pyd\testkit\web\Csrf;

/**
 * Send an Ajax request and store response data.
 * 
 * A javascript is injected and executed in the current page and its response
 * data stored in browser storage (sessionStorage).
* 
 * <code>
 * $xhr = new XmlHttpRequest();
 * $xhr->setMethod('POST')->setBody(['name' => 'value'])->send();
 * $this->assertEquals(302, $xhr->getStatus());
 * </code>
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class XmlHttpRequest extends \yii\base\Object
{
    /**
     * Name of the browser storage variable that contains reqeust response data.
     */
    const XHR_DATA_STORAGE_KEY = 'pyd.testkit.xhr.data';
    /**
     * Route of the request.
     * @var string request route
     */
    public $route;
    /**
     * Method of the request.
     * @var string
     */
    protected $method = 'GET';
    /**
     * Body of the request.
     * @see resolveBody()
     * @var null|array request 'POST' data 
     */
    protected $body;
    /**
     * Should the csrf token be sent with the request body?
     * @see resolveBody()
     * @var boolean 
     */
    public $sendCsrfToken = true;
    /**
     * @var \pyd\testkit\web\RemoteDriver 
     */
    protected $webDriver;
    
    /**
     * @param RemoteDriver $webDriver
     * @param array $config
     */
    public function __construct(RemoteDriver $webDriver, array $config = [])
    {
        $this->webDriver = $webDriver;
    }
    
    /**
     * Set request method.
     * @see $method
     * @param string $method param is case insensitive
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
        return $this;
    }
    
    /**
     * Set request body content.
     * @see $body 
     * @param array $body an array of key=>value pairs
     * @return $this
     */
    public function setBody(array $body)
    {
        $this->body = $body;
        return $this;
    }
    
    /**
     * Send the request.
     * 
     * @param array $urlParams
     * @param int $waitTimeout time to wait - in seconds - for request completion
     * Set it to 0 to skip wait.
     * @return $this
     */
    public function send(array $urlParams = [], $waitTimeout = 5)
    {
        $script = $this->generateScript($urlParams);
        
        $script = "var pyd = {testkit:{}};
                   (function() { $script }).apply(pyd.testkit);";
        $this->webDriver->executeScript($script);
        return $this;
    }

    /**
     * Send the request and wait until the xhr.readyState returns 'DONE'.
     * 
     * @param array $urlParams
     * @param int $waitTimeout time to wait - in seconds - for request to complete
     * @return $this
     */
    public function sendAndWait(array $urlParams = [], $waitTimeout = 5)
    {
        $this->send($urlParams);
        $this->waitReadyStateDone($waitTimeout);
        return $this;
    }
    
    /**
     * Generate the javascript to send the XHR, handle the response and store
     * some of its data in browser session storage.
     * 
     * Stored data:
     * - 'status.code' => xhr.status;
     * - 'statusText' => xhr.statusText;
     * - 'headers'=> xhr.getAllResponseHeaders();
     * - 'response' => xhr.response;
     * 
     * @param array $urlParams 
     * @return string
     * @throws InvalidCallException 
     */
    protected function generateScript(array $urlParams = [])
    {
        $url = $this->createUrl($urlParams);
        $xhrDataStorageKey = self::XHR_DATA_STORAGE_KEY;
        
        $script = <<<EOT
var xhr = null;
sessionStorage.removeItem("$xhrDataStorageKey");
if (window.XMLHttpRequest || window.ActiveXObject) {
    if (window.ActiveXObject) {
        try {
            xhr = new ActiveXObject("Msxml2.XMLHTTP");
        } catch(e) {
            xhr = new ActiveXObject("Microsoft.XMLHTTP");
        }
    } else {
        xhr = new XMLHttpRequest(); 
    }
} else {
    alert("this browser (" + navigator.userAgent + ") does not support XMLHTTPRequest.");
}
xhr.open("$this->method", "$url", true);
xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
        var data = {
            "status": xhr.status,
            "statusText": xhr.statusText,
            "headers":xhr.getAllResponseHeaders(),
            "response":xhr.response    
        };
        sessionStorage.setItem("$xhrDataStorageKey", JSON.stringify(data));
    }
}
EOT;
        if ('GET' === $this->method || 'HEAD' === $this->method) {
            $body = '';
            
        } else {
            $body = $this->resolveBody();
            if (!empty($body)) {
                $script .= "\n" . 'xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");';
                $body = http_build_query($body);
            } else {
                $body = '';
            }
        }
        
        // If the request method is GET or HEAD, the body parameter is ignored
        // and request body is set to null.
        $script .= "\nxhr.send('$body')";
        
        return $script;
    }
    
    /**
     * Generate the body to be send with the request based on the {@see $body}
     * and {@see $sendCsrfToken} properties.
     * 
     * If {@see $sendCsrfToken} is true and {@see $body} does not contain
     * the {@see \yii\web\Request::$csrfParam} key, this method will try to
     * find the csrf token in the current page and add it to the returned body.
     * If it fails to find the token, an InvalidCallException is thrown.
     * If {@see $sendCsrfToken} is false and {@see $body} does contain
     * the {@see \yii\web\Request::$csrfParam} key, this item will be removed
     * of the returned body.
     * 
     * @return array
     * @throws InvalidCallException
     */
    protected function resolveBody()
    {
        $body = (!empty($this->body)) ? $this->body : [];
        $csrfParam = \Yii::$app->getRequest()->csrfParam;
        
        if ($this->sendCsrfToken && !array_key_exists($csrfParam, $body)) {
            $csrfToken = (new Csrf($this->webDriver))->getToken();
            if (null !== $csrfToken) {
                $body[$csrfParam] = $csrfToken;
            } else {
                throw new InvalidCallException("Cannot find a csrf token in the current page."
                        . " You might set it manually in the " . get_class() . "::$body property.");
            }
        } else if (!$this->sendCsrfToken && array_key_exists($csrfParam, $body)) {
            unset($body[$csrfParam]);
        }
        return $body;
    }
    
    /**
     * Create url.
     * 
     * @param array $urlParams
     * @return string
     */
    public function createUrl(array $urlParams = [])
    {
        array_unshift($urlParams, $this->route);
        return Url::to($urlParams);
    }
    
    private $xhrData = null;
    /**
     * Get xhr data saved in browser session storage after request is DONE.
     * 
     * Stored data:
     * - 'status' => xhr.status;
     * - 'headers'=> xhr.getAllResponseHeaders();
     * - 'response' => xhr.response;
     * 
     * @return array
     */
    public function getXhrData()
    {
        if (null === $this->xhrData) {
            $data = json_decode($this->webDriver->executeScript('return sessionStorage.getItem("' .self::XHR_DATA_STORAGE_KEY. '");'), true);
            // xhr.getAllResponseHeaders() returns all headers in a string with this format:
            //  "headers": "Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0\r\nConnection: keep-alive\r\nContent-Type: text/html; charset=UTF-8\r\n....\r\n"
            $headers = trim($data['headers']);      // there's an ending \r\n
            $headers = explode(PHP_EOL, $headers);  // explode does not work as expected with '\r\n'
            $data['headers'] = [];
            foreach ($headers as $header) {
                list($name, $value) = explode(': ', $header);
                $data['headers'][$name] = $value;
            }
            $this->xhrData = $data;
        }
        return $this->xhrData;
    }
    
    /**
     * Get the value of xhr.status.
     * 
     * @return int the http code returned by the xhr request
     */
    public function getResponseStatusCode()
    {
        return $this->getXhrData()['status'];
    }
    
    /**
     * Get the value of xhr.statusText.
     * 
     * @return string
     */
    public function getResponseStatusText()
    {
        return $this->getXhrData()['statusText'];
    }
    /**
     * Get the value of xhr.response.
     * 
     * @return string
     */
    public function getXhrResponse()
    {
        return $this->getXhrData()['response'];
    }
    
    /**
     * Get the value of xhr.getAllResponseHeaders().
     * 
     * @return array
     */
    public function getXhrResponseHeaders()
    {
        return $this->getXhrData()['headers'];
    }
    
    /**
     * Get the value of a specific header.
     * 
     * @param string $name header name
     * @return string 
     * @throws InvalidParamException header is not present
     */
    public function getXhrResponseHeader($name)
    {
        $headers = $this->getXhrResponseHeaders();
        if (array_key_exists($name, $headers)) {
            return $headers[$name];
        } else {
            throw new InvalidParamException("Response does not have a '$name' header.");
        }
    }
    
    /**
     * Wait until the XHR request is complete.
     * 
     * @param int $timeout stop waiting after $timeout seconds
     */
    protected function waitReadyStateDone($timeout = 5)
    {
        $this->webDriver->wait($timeout)->until(function() {
            // session storage variable is set when the xhr request is done
            return $this->webDriver->executeScript('return null !== sessionStorage.getItem("' .self::XHR_DATA_STORAGE_KEY. '");');
        });
    }       
}
