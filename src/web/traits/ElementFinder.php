<?php
namespace pyd\testkit\web\traits;

use pyd\testkit\web\element\Helper as ElementHelper;

use yii\helpers\ArrayHelper;

/**
 * This trait provides methods to find web elements.
 *
 * When used by a page object, the searching scope is the DOM.
 *
 * When used by an element object, the searching scope is the element itself.
 * Well, it should be. There's one exception:
 * https://code.google.com/p/selenium/issues/detail?id=403
 * <code>
 * // will return all <a> of the DOM, not only the ones of the menu
 * $sideMenu->findElements(['xpath', '//a');
 * // to be sure to limit the search to the child elements use a DOT
 * $sideMenu->findElements(['xpath', './/a');
 * </code>
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
trait ElementFinder
{
    /**
     * Default reference used to create element objects.
     *
     * @see createElement()
     *
     * @var string|array a class name or an array with at least a 'class' key
     */
    public $elementReference = ['class' => '\pyd\testkit\web\Element'];

    /**
     * Get the first web element matching the locator param.
     *
     * @param array|\WebDriverBy $locator web element locator
     * @param string|array $reference reference for returned object
     * @return \pyd\testkit\web\Element
     */
    public function findElement($locator, $reference = null)
    {
        $by = $this->resolveLocator($locator);
        $id = $this->findElementId($by);
        return $this->createElement($id, $reference);
    }

    /**
     * Get all web elements matching the locator param.
     *
     * @param array|\WebDriverBy $locator web element locator
     * @param string|array $reference reference for returned objects
     * @return array empty if no web element matches $by
     */
    public function findElements($locator, $reference = null)
    {
        $by = $this->resolveLocator($locator);
        $elements = [];
        $ids = $this->findElementIds($by);
        foreach ($ids as $id) {
            $elements[] = $this->createElement($id, $reference);
        }
        return $elements;
    }

    /**
     * Get the selenium ID of the first web element matching $by.
     *
     * If this method is used by a page object, the web element will be searched
     * in the DOM. If used by an element object, it will be searched within this
     * element unless the $by parameter is an 'xpath' expression starting with
     * '//'. In this case it will be searched in the DOM.
     *
     * @param \WebDriverBy $by
     * @return string element ID
     * @throws \NoSuchElementException if no web element matches $by
     */
    protected function findElementId(\WebDriverBy $by)
    {
        $command = $this instanceof \pyd\testkit\web\Element ? \DriverCommand::FIND_CHILD_ELEMENT : \DriverCommand::FIND_ELEMENT;
        $response = $this->execute($command, ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        return $response['ELEMENT'];
    }

    /**
     * Get selenium IDs of all web elements matching $by.
     *
     * If this method is used by a page object, the web element will be searched
     * in the DOM. If used by an element object, it will be searched within this
     * element unless the $by parameter is an 'xpath' expression starting with
     * '//'. In this case it will be searched in the DOM.
     *
     * @param \WebDriverBy $by
     * @return array element IDs. It can be empty if no web element matches $by.
     */
    protected function findElementIds(\WebDriverBy $by)
    {
        $ids = [];
        $command = $this instanceof \pyd\testkit\web\Element ? \DriverCommand::FIND_CHILD_ELEMENTS : \DriverCommand::FIND_ELEMENTS;
        $response = $this->execute($command, ['using' => $by->getMechanism(), 'value' => $by->getValue()]);
        foreach ($response as $item) $ids[] = $item['ELEMENT'];
        return $ids;
    }

    /**
     * Create a web element.
     *
     * @note if $reference is not null, it will be merged with
     * {@see $elementReference}.
     *
     * @param string $id selenium ID of the web element
     * @param string|array $reference reference of the created object
     * @return \pyd\testkit\web\Element
     */
    protected function createElement($id, $reference)
    {
        if (null === $reference) {
            $reference = $this->elementReference;
        } else {
            $defaultReference = $this->elementReference;
            if (is_string($defaultReference)) $defaultReference = ['class' => $defaultReference];
            if (is_string($reference)) $reference = ['class' => $reference];
            $reference = ArrayHelper::merge($defaultReference, $reference);
        }
        return \Yii::createObject($reference, [$this->webDriver, $id]);
    }

    /**
     * @param \WebDriverBy $locator
     * @return \WebDriverBy
     * @throws \InvalidArgumentException
     */
    protected function resolveLocator($locator)
    {
        if ($locator instanceof \WebDriverBy) return $locator;
        if (is_array($locator)) return ElementHelper::createWebDriverByFromArray($locator);
        throw new \InvalidArgumentException("\$locator must be an array or instance of \WebDriverBy.");
    }

    /**
     * Send command to selenium.
     * @see \pyd\testkit\web\Element::execute
     * @see \pyd\testkit\functional\base\Page::execute
     */
    abstract protected function execute($command, array $params = []);
}
