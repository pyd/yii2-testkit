<?php
namespace pyd\testkit\web\elements;

/**
 * <select> element.
 *
 * @author Pierre-Yves DELETTRE <pierre.yves.delettre@gmail.com>
 */
class Select extends \pyd\testkit\web\Element
{
    private $isMulti;

    public function isMultiple()
    {
        if (null === $this->isMulti) {
            $this->isMulti = (true === $this->getAttribute('multiple'));
        }
        return $this->isMulti;
    }

    /**
     * @return array All selected options belonging to this select tag.
     */
    public function getAllSelectedOptions() {
        $options = [];
        foreach ($this->options as $option) {
            if ($option->isSelected()) {
                $options[] = $option;
            }
        }
        return $options;
    }

    /**
     * @return WebDriverElement The first selected option in this select tag (or
     * the currently selected option in a normal select)
     */
    public function getFirstSelectedOption() {
        foreach ($this->options as $option) {
            if ($option->isSelected()) {
                return $option;
            }
        }
        throw new \NoSuchElementException('No options are selected');
    }

    /**
     *
     * @return string value of the first selected option.
     */
    public function getFirstSelectedOptionValue()
    {
        return $this->getFirstSelectedOption()->getAttribute('value');
    }

    /**
     * Deselect all options in multiple select tag.
     *
     * @return void
     */
    public function deselectAll() {
        if (!$this->isMultiple()) {
            throw new \UnsupportedOperationException('You may only deselect all options of a multi-select');
        }

        foreach ($this->options as $option) {
            if ($option->isSelected()) {
                $option->click();
            }
        }
    }

    /**
     * Select the option at the given index.
     *
     * @param int $index The index of the option. (0-based)
     * @return void
     */
    public function selectByIndex($index) {
        $matched = false;

        foreach ($this->options as $option) {
            if ($option->getAttribute('index') === (string)$index) {
                if (!$option->isSelected()) {
                    $option->click();
                    if (!$this->isMultiple()) {
                        return;
                    }
                }
                $matched = true;
            }
        }

        if (!$matched) {
            throw new \NoSuchElementException(sprintf('Cannot locate option with index: %d', $index));
        }
    }

    /**
     * Select all options that have value attribute matching the argument. That
     * is, when given "foo" this would select an option like:
     *
     * <option value="foo">Bar</option>;
     *
     * @param string $value The value to match against.
     * @return void
     */
    public function selectByValue($value) {
        $matched = false;
        $xpath = './/option[@value = '.$this->escapeQuotes($value).']';
        $options = $this->findElements(\WebDriverBy::xpath($xpath));

        foreach ($options as $option) {
            if (!$option->isSelected()) {
                $option->click();
            }
            if (!$this->isMultiple()) return;
            $matched = true;
        }

        if (!$matched) {
            throw new \NoSuchElementException(sprintf('Cannot locate option with value: %s', $value));
        }
    }

    /**
     * Select all options that display text matching the argument. That is, when
     * given "Bar" this would select an option like:
     *
     * <option value="foo">Bar</option>;
     *
     * @param string $text The visible text to match against.
     * @return void
     */
    public function selectByVisibleText($text)
    {
        $matched = false;
        $xpath = './/option[normalize-space(.) = '.$this->escapeQuotes($text).']';
        $options = $this->findElements(\WebDriverBy::xpath($xpath));

        if (!$option->isSelected()) {
            foreach ($options as $option) {
                $option->click();
            }
            if (!$this->isMultiple()) return;
            $matched = true;
        }

        // Since the mechanism of getting the text in xpath is not the same as
        // webdriver, use the expensive getText() to check if nothing is matched.
        if (!$matched) {
            foreach ($this->options as $option) {
                if ($option->getText() === $text) {
                    if (!$option->isSelected()) {
                        $option->click();
                    }
                    if (!$this->isMultiple()) return;
                    $matched = true;
                }
            }
        }

        if (!$matched) {
            throw new \NoSuchElementException(sprintf('Cannot locate option with text: %s', $text));
        }
    }

    /**
     * Deselect the option at the given index.
     *
     * @param int $index The index of the option. (0-based)
     * @return void
     */
    public function deselectByIndex($index)
    {
        foreach ($this->options as $option) {
            if ($option->getAttribute('index') === (string)$index && $option->isSelected()) {
                $option->click();
            }
        }
    }

    /**
     * Deselect all options that have value attribute matching the argument. That
     * is, when given "foo" this would select an option like:
     *
     * <option value="foo">Bar</option>;
     *
     * @param string $value The value to match against.
     * @return void
     */
    public function deselectByValue($value) {
        $xpath = './/option[@value = '.$this->escapeQuotes($value).']';
        $options = $this->findElements(\WebDriverBy::xpath($xpath));

        foreach ($options as $option) {
            if ($option->isSelected()) {
                $option->click();
            }
        }
    }

    /**
     * Deselect all options that display text matching the argument. That is, when
     * given "Bar" this would select an option like:
     *
     * <option value="foo">Bar</option>;
     *
     * @param string $text The visible text to match against.
     * @return void
     */
    public function deselectByVisibleText($text)
    {
        $xpath = './/option[normalize-space(.) = '.$this->escapeQuotes($text).']';
        $options = $this->findElements(WebDriverBy::xpath($xpath));

        foreach ($options as $option) {
            if ($option->isSelected()) {
                $option->click();
            }
        }
    }

    /**
     * Convert strings with both quotes and ticks into:
     *   foo'"bar -> concat("foo'", '"', "bar")
     *
     * @param string $to_escape The string to be converted.
     * @return string The escaped string.
     */
    protected function escapeQuotes($to_escape)
    {
        if (strpos($to_escape, '"') !== false && strpos($to_escape, "'") !== false) {

            $substrings = explode('"', $to_escape);
            $escaped = "concat(";
            $first = true;

            foreach ($substrings as $string) {

                if (!$first) {
                    $escaped .= ", '\"',";
                    $first = false;
                }
                $escaped .= '"' . $string . '"';
            }

            return $escaped;
        }

        if (strpos($to_escape, '"') !== false) {
            return sprintf("'%s'", $to_escape);
        }

        return sprintf('"%s"', $to_escape);
    }

    protected function initLocators()
    {
        $this->locator->add('options', \WebDriverBy::tagName('option'));
    }
}
