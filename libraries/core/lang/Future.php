<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

class Future implements IFuture
{
    protected $_value;
    protected $_callback;

    public static function factory($input)
    {
        if ($input instanceof IFuture) {
            return $input;
        }

        return new self($input);
    }

    public function __construct($callback)
    {
        $this->_callback = Callback::factory($callback);
    }

    public function __invoke()
    {
        return $this->getValue();
    }

    public function setValue($value)
    {
        $this->_value = $value;
        $this->_callback = null;
        return $this;
    }

    public function getValue($default = null)
    {
        if ($this->_callback) {
            $this->_value = $this->_callback->invoke();
            $this->_callback = null;
        }

        if (null === ($output = $this->_value)) {
            $output = $default;
        }

        return $output;
    }
}
