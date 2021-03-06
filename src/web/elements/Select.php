<?php
namespace pyd\testkit\web\elements;

use pyd\testkit\AssertionMessage;
use NoSuchElementException;

/**
 * A <select> element.
 * 
 * @property array $options <options> elements of this <select>
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
     * @return array <options> elements of this <select>
     */
    public function getOptions()
    {
        return $this->findElements(\WebDriverBy::tagName('option'));
    }
    
    /**
     * Get a map - value => text pairs - of all options.
     * 
     * @param boolean $withPrompt include prompt option
     * @return array
     */
    public function getOptionsMap($withPrompt = false)
    {
        $map = [];
        foreach ($this->getOptions() as $option) {
            $map[$option->getAttribute('value')] = $option->getText();
        }
        if (!$withPrompt) {
            unset($map['']);
        }
        return $map;
    }
    
    /**
     * Get values - tag attribute - of all options.
     * 
     * @param string $promptValue value of the prompt option
     * @param boolean $excludePromptValue do not return the prompt value
     * @return array note that excluding the prompt may return an array with 1 as
     * first key instead of 0
     */
    public function getOptionValues($promptValue = '', $excludePromptValue = true)
    {
        $values = [];
        foreach ($this->getOptions() as $option) {
            $values[] = $option->getAttribute('value');
        }
        if ($excludePromptValue) {
            unset($values[array_search($promptValue, $values)]);
        }
        return $values;
    }
    
    /**
     * @return array All selected options belonging to this select tag.
     */
    public function getAllSelectedOptions() {
        $options = [];
        foreach ($this->getOptions() as $option) {
            if ($option->isSelected()) {
                $options[] = $option;
            }
        }
        return $options;
    }
    
    /**
     * Check if the text of the selected option in a non multiple <SELECT>
     * element matches the $text param.
     * 
     * @param string text
     * @return boolean
     * @throws \yii\base\InvalidCallException this method is not meant to be
     * used with 'multiple' <SELECT> element
     */
    public function selectedTextIs($text)
    {
        if ($this->isMulti) {
            throw new \yii\base\InvalidCallException("Method " . __METHOD__ . " is not meant to be used with 'multiple' <SELECT> element.");
        }
        $selectedText = $this->getFirstSelectedOption()->getText();
        if ($text == $selectedText) {
            AssertionMessage::set("Selected text is '$text'.");
            return true;
        } else {
            AssertionMessage::set("Selected text is not '$text' but '$selectedText'.");
            return false;
        }
    }
    
    /**
     * Check if the value of the selected option in a non multiple <SELECT>
     * element matches the $value param.
     * 
     * @param string|int $value
     * @return boolean
     * @throws \yii\base\InvalidCallException this method is not meant to be
     * used with 'multiple' <SELECT> element
     */
    public function selectedValueIs($value)
    {
        if ($this->isMulti) {
            throw new \yii\base\InvalidCallException("Method " . __METHOD__ . " is not meant to be used with multiple <SELECT> element.");
        }
        $selectedValue = $this->getFirstSelectedOption()->getAttribute('value');
        if ($value == $selectedValue) {
            AssertionMessage::set("Selected value is '$value'.");
            return true;
        } else {
            AssertionMessage::set("Selected value is not '$value' but '$selectedValue'.");
            return false;
        }
    }

    /**
     * @return WebDriverElement The first selected option in this select tag (or
     * the currently selected option in a normal select)
     */
    public function getFirstSelectedOption() {
        foreach ($this->getOptions() as $option) {
            if ($option->isSelected()) {
                return $option;
            }
        }
        throw new \NoSuchElementException('No options are selected');
    }

    /**
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

        foreach ($this->getOptions() as $option) {
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

        foreach ($this->getOptions() as $option) {
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
            foreach ($this->getOptions() as $option) {
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
        foreach ($this->getOptions() as $option) {
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
     * Check if there's an <option> element with the given value.
     * 
     * @param string|int $value
     * @return boolean
     */
    public function hasOptionValue($value)
    {
        if ($this->hasElement(\WebDriverBy::cssSelector("option[value=\"$value\"]"))) {
            AssertionMessage::set("Select element has option with value '$value'.");
            return true;
        } else {
            AssertionMessage::set("Select element does not have option with value '$value'.");
            return false;
        }
    }
    
    /**
     * Check if the <option> elements have the expected values.
     * 
     * Note that the 'prompt' <option>, defined by the $promptValue parameter
     * is excluded from comparison.
     * 
     * @param array $values <option> values to look for in the actual <option> values
     * @param boolean $strict in 'strict' mode, true is returned if given values
     * exactly match actual ones. In 'non strict' mode, true is returned if
     * given values are present in actual ones.
     * @param string $promptValue value of the prompt <option>
     * @return boolean
     */
    public function hasOptionValues(array $values, $strict = true, $promptValue = '')
    {
        // get <option> values, except the value of the prompt
        $actualValues = [];
        foreach ($this->getOptions() as $option) {
            $value = $option->getAttribute('value');
            if ($value !== $promptValue) {
                $actualValues[] = $value;
            }
        }
        // search for unexpected and missing values
        $unexpectedActual = array_diff($actualValues, $values);
        $missingActual = array_diff($values, $actualValues);
        
        AssertionMessage::clear();
        if ([] !== $unexpectedActual) {
            AssertionMessage::add("Unexpected option value(s) [" .implode(', ', $unexpectedActual). "] in dropdownlist.", true);
        }
        if ([] !== $missingActual) {
            AssertionMessage::add("Missing option value(s) [" .implode(', ', $missingActual). "] in dropdownlist.", true);
        }
       
        if ($strict) {
            return [] === $unexpectedActual && [] === $missingActual;
        } else {
            return [] === $missingActual;
        }
    }
    
    /**
     * Get an <option> element by it's value attribute.
     * 
     * @param string $value value of the 'value' attribute
     * @return \pyd\testkit\web\base\Element
     * @throws \NoSuchElementException no <option> matching 'value' attribute
     */
    public function getOptionByValue($value)
    {
        foreach ($this->getOptions() as $option) {
            if ($value == $option->getAttribute('value')) {
                return $option;
            }
        }
        throw new \NoSuchElementException("There's no option element with value '$value'.");
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
}
