<?php
namespace pyd\testkit\web\elements;

/**
 * Summary element of a GridView.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class GridViewSummary extends \pyd\testkit\web\Element
{
    /**
     * @var pattern to extract total count from GridView summary element
     */
    public $totalCountPattern = "` (?P<total>[0-9]+) `";

    /**
     * Read the total of items from the gridView summary.
     *
     * @return integer grid view elements total count
     * @throws \LogicException failed to extract total count
     */
    public function getTotalItems()
    {
        $text = $this->getText();
        $result = preg_match($this->totalCountPattern, $text, $matches);
        if (1 === $result) {
            return $matches['total'];
        }
        throw new \LogicException("Cannot extract grid summary total count.");
    }
}
