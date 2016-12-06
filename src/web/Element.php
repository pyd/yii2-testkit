<?php
namespace pyd\testkit\web;

/**
 * @brief ...
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Element extends base\ElementWrapper
{
    use base\ElementContainerTrait;

    public function init()
    {
        $this->initElementContainerTrait();
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->locators)) {
            return $this->findElement($this->locators[$name]);
        }
        return parent::__get($name);
    }
}
