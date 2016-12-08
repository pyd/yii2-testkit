<?php
namespace pyd\testkit\web\elements;

/**
 * A form input of type text, password
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class TextInput extends \pyd\testkit\web\base\ElementWrapper
{
    /**
     * @return string the input value
     */
    public function getValue()
    {
        return $this->getAttribute('value');
    }

    /**
     * @param string $value the input value
     */
    public function setValue($value)
    {
        $this->setAttribute('value', $value);
    }
}
