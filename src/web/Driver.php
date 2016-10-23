<?php
namespace pyd\testkit\web;

/**
 * Custom implementation of FB RemoteWebDriver class.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Driver extends \RemoteWebDriver
{
    /**
     * Return an object to manage cookies.
     *
     * @return \pyd\testkit\functional\base\Cookies
     */
    public function cookies()
    {
        return new Cookies($this->getExecuteMethod());
    }
}
