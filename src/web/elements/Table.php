<?php
namespace pyd\testkit\web\elements;

/**
 * An html table.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Table extends \pyd\testkit\web\Element
{
    public function __construct($id, array $config = array())
    {
        parent::__construct($id, $config);
        $tagName = $this->getTagName();
        if ('table' !== $tagName) {
            throw new UnexpectedTagNameException('table', $tagName);
        }
    }

    protected function initLocators()
    {
        parent::initLocations();
        $this->locator->add('caption', \WebDriverBy::tagName('caption'));
        $this->locator->add('headers', \WebDriverBy::tagName('th'));
        $this->locator->add('row', $location);
    }

    /**
     * @return array \pyd\testkit\web\base\Element
     */
    public function getHeaders()
    {
        return $this->findElements('headers')->asA(\pyd\testkit\web\Element::className());
    }

    /**
     * @return array header cellls lables
     */
    public function getHeaderLabels()
    {
        $labels = [];
        foreach ($this->getHeaders() as $cell) {
            $labels = $cell->getText();
        }
        return $labels;
    }
}
