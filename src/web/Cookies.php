<?php
namespace pyd\testkit\web;

/**
 * Manage cookies.
 *
 * Note that selenium server won't let you get/set cookies unless a page of the
 * same domain is currently displayed in your browser.
 * You first have to load a page to be able to get/set a cookie for the same
 * domain.
 *
 * WebDriver provides a shortcut to get an instance of this class:
 * @see \pyd\testkit\functional\base\WebDriver::cookies()
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Cookies extends \yii\base\Object
{
    /**
     * @var \ExecuteMethod
     */
    protected $executor;

    public function __construct(\ExecuteMethod $executor, $config = array())
    {
        parent::__construct($config);
        $this->executor = $executor;
    }

    /**
     * Add a cookie.
     *
     * @param \yii\web\Cookie $cookie
     * @throws \InvalidCookieDomainException an illegal attempt was made to set
     * a cookie under a different domain than the current page
     * @throws \UnableToSetCookie a request to set a cookie's value could not be
     * satisfied.
     */
    public function add(\yii\web\Cookie $cookie)
    {
        $data = get_object_vars($cookie);
        if (\Yii::$app->getRequest()->enableCookieValidation) {
            // serialization will insert semicolons in the string
            // note that semicolons are not allowed in cookie value RFC 6265 4.1.1
            // urlencode will prevent PHP to see semicolons as cookies separator
            $value = serialize([$data['name'], $data['value']]);
            $value = \Yii::$app->getSecurity()->hashData($value, \Yii::$app->getRequest()->cookieValidationKey);
            $data['value'] = urlencode($value);
        }
        $this->executor->execute(\DriverCommand::ADD_COOKIE, array('cookie' => $data));
    }

   /**
    * Get the cookie with a given name.
    *
    * @param string $name
    * @return \yii\web\Cookie|null null si le cookie n'xiste pas
    */
    public function getByName($name)
    {
        foreach ($this->getAll() as $cookie) {
            if ($cookie->name === $name) {
                return $cookie;
            }
        }
        return null;
    }

   /**
    * Delete all the cookies that are currently visible.
    *
    * @return \pyd\testkit\functional\base\Cookies the current instance
    */
    public function deleteAll()
    {
        $this->executor->execute(\DriverCommand::DELETE_ALL_COOKIES);
        return $this;
    }

   /**
    * Delete the cookie with the give name.
    *
    * @param string $name
    * @return \pyd\testkit\functional\base\Cookies the current instance
    */
    public function deleteByName($name)
    {
        $this->executor->execute(\DriverCommand::DELETE_COOKIE, array(':name' => $name));
        return $this;
    }

    /**
     * Get all the cookies for the current domain.
     *
     * @return array of \yii\web\Cookie objects or empty
     * @throws \LogicException
     */
    public function getAll()
    {
        $cookies = [];
        $validateCookie = \Yii::$app->getRequest()->enableCookieValidation;
        $cookieValidationKey = \Yii::$app->getRequest()->cookieValidationKey;

        foreach ($this->executor->execute(\DriverCommand::GET_ALL_COOKIES) as $cookieData) {
            // each array has the following keys: name, path, value, secure, domain, class, httpOnly, hCode
            // class contains "org.openqa.selenium.Cookie" and hCode a selenium internal code e.g. int(90797955)
            // they won't be needed to create a \yii\web\Cookie object
            unset($cookieData['class'], $cookieData['hCode']);

            if ($validateCookie) {
                $value = $cookieData['value'];
                $value = urldecode($value);
                $value = \Yii::$app->getSecurity()->validateData($value, $cookieValidationKey);
                if (false === $value) {
                    throw new \LogicException("Cannot validate cookie named '" .$cookieData['name']. "'. It's value was modified.");
                }
                $value = @unserialize($value);      // value is now an array [$cookieName, $cookieValue]
                if (is_array($value) && isset($value[0], $value[1]) && $value[0] === $cookieData['name']) {
                    $cookieData['value'] = $value[1];
                } else {
                    throw new \yii\base\Exception("Cookie named " . $cookieData['name'] . " is invalid.");
                }

            }

            $cookies[] = new \yii\web\Cookie($cookieData);
        }

        return $cookies;
    }
}
