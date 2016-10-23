<?php
namespace pyd\testkit\web\traits;

use pyd\testkit\web\element\Helper as ElementHelper;
use yii\base\InvalidCallException;

/**
 * Identify a web element locator with an alias.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
trait LocatorAlias
{
    private $_locators = [];

    public function addLocator($alias, $locator, $overwrite = false)
    {
        if (isset($this->_locators[$alias]) && !$overwrite) {
            throw new InvalidCallException("Locator alias '$alias' already exists.");
        }
        if ($locator instanceof \WebDriverBy) {
            $this->_locators[$alias] = $locator;
        } else if (is_array($locator)) {
            $this->_locators[$alias] = ElementHelper::createWebDriverByFromArray($locator);
        } else {
            throw new \InvalidArgumentException("Locator must be an array or a \WebDriverBy object.");
        }
    }

    public function getLocator($alias)
    {
        if (isset($this->_locators[$alias])) return $this->_locators[$alias];
        throw new InvalidCallException("Unknown locator alias '$alias'.");
    }

    public function getLocators()
    {
        return $this->_locators;
    }

    public function hasLocator($alias)
    {
        return isset($this->_locators[$alias]);
    }
}
