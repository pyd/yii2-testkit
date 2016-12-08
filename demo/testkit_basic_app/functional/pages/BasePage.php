<?php
namespace tests\functional\pages;

use pyd\testkit\functional\base\ElementContainerElement;
use tests\functional\containers\Breadcrumb;

/**
 * Base class for page objects. You can add here your custom methods
 *
 * @license see the yii2-testkit/LICENSE file.
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class BasePage extends \pyd\testkit\web\Page {

    public function init()
    {
        parent::init();

        $this->locator->addLocation('navbar', 'id', 'w0');
        $this->locator->addLocation('breadcrumb', 'cssSelector', 'ul.breadcrumb');

        // training: add a 'footer' location here.
        // Look at the page source to define a mechanism and a value for this location
        
    }

    /**
     * @return Breadcrumb
     */
    public function getBreadcrumbContainer()
    {
        return $this->findElementAsContainer('breadcrumb', Breadcrumb::className());
    }

}
