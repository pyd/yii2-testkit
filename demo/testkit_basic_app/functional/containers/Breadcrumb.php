<?php
namespace tests\functional\containers;

/**
 * Breadcrumb web element as a container.
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Breadcrumb extends \pyd\testkit\functional\base\ElementContainerElement {


    public function init()
    {
        parent::init();

        // $breadcrumb->labels will return an array of web elements (all <li>)
        $this->locator->addLocation('labels', 'tagName', 'li');

        // training: add a location for the active label of the breadcrumb
        // use the alias 'activeLabel'
        // remember, this is a relative location: active label is a breadcrumb child
        // ...
    }

    /**
     * Get text of the active label.
     *
     * @return string active label text
     */
    public function getActiveLabelText() {

        // training: return the 'activeLabel' element text
        // ...
    }
}
