<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class InputTree extends Tree implements IInputTree
{
    use TErrorContainer;

    public static function factory($input)
    {
        if ($input instanceof IInputTree) {
            return $input;
        }

        return new self($input);
    }

    protected function _getSerializeValues()
    {
        $output = parent::_getSerializeValues();

        if (!empty($this->_errors)) {
            if ($output === null) {
                $output = [];
            }

            $output['er'] = $this->_errors;
        }

        return $output;
    }

    protected function _setUnserializedValues(array $values)
    {
        parent::_setUnserializedValues($values);

        if (isset($values['er'])) {
            $this->_errors = $values['er'];
        }
    }

    public function importTree(ITree $child)
    {
        if ($child instanceof IInputTree) {
            $this->_errors = $child->getErrors();
        }

        return parent::importTree($child);
    }

    public function merge(ITree $child)
    {
        if ($child instanceof IInputTree) {
            $this->addErrors($child->getErrors());
        }

        return parent::importTree($child);
    }

    public function isValid(): bool
    {
        if ($this->hasErrors()) {
            return false;
        }

        foreach ($this->_collection as $child) {
            if (!$child->isValid()) {
                return false;
            }
        }

        return true;
    }

    public function countErrors(): int
    {
        $output = count($this->_errors);

        foreach ($this->_collection as $child) {
            $output += $child->countErrors();
        }

        return $output;
    }

    public function toArrayDelimitedErrorSet($prefix=null)
    {
        $output = [];

        if ($prefix && !empty($this->_errors)) {
            $output[$prefix] = $this->_errors;
        }

        foreach ($this as $key => $child) {
            if ($prefix) {
                $key = $prefix.'['.$key.']';
            }

            $output = array_merge($output, $child->toArrayDelimitedErrorSet($key));
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if ($this->_value !== null) {
            yield 'property:*value' => $this->_value;
        }

        if (!empty($this->_errors)) {
            yield 'property:*errors' => $this->_errors;
        }

        foreach ($this->_collection as $key => $child) {
            if ($child instanceof self && empty($child->_collection) && empty($child->_errors)) {
                yield 'value:'.$key => $child->_value;
            } else {
                yield 'value:'.$key => $child;
            }
        }
    }
}
