<?php
namespace pyd\testkit\web;

/**
 * A web element.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Element extends \pyd\testkit\web\element\Base
{
    use traits\ElementContainer;

    public function init()
    {
        parent::init();
        $this->initLocators();
    }
}
