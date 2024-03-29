<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\schema;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\opal;

class IndexFieldReference implements IIndexFieldReference, Dumpable
{
    protected $_field;
    protected $_size;
    protected $_isDescending = false;

    public function __construct(opal\schema\IField $field, $size = null, $isDescending = false)
    {
        if (is_string($isDescending)) {
            $isDescending = strtoupper($isDescending) == 'DESC';
        }

        if ($isDescending === null) {
            $isDescending = false;
        }

        $this->_setField($field);
        $this->setSize($size);
        $this->isDescending((bool)$isDescending);
    }

    public function _setField(opal\schema\IField $field)
    {
        $this->_field = $field;
        return $this;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function isMultiField()
    {
        return $this->_field instanceof opal\schema\IMultiPrimitiveField;
    }

    public function setSize($size)
    {
        if ($size !== null) {
            $size = (int)$size;
        }

        if ($size == 0) {
            $size = null;
        }

        $this->_size = $size;
        return $this;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function isDescending(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isDescending = $flag;
            return $this;
        }

        return $this->_isDescending;
    }

    public static function fromStorageArray(opal\schema\ISchema $schema, array $data)
    {
        if (!$schema instanceof opal\schema\IFieldProvider) {
            throw Exceptional::Runtime(
                'Schema does not provider fields',
                null,
                $schema
            );
        }

        return new self($schema->getField($data['fld']), $data['siz'], $data['des']);
    }

    public function toStorageArray()
    {
        return [
            'fld' => $this->_field->getName(),
            'siz' => $this->_size,
            'des' => $this->_isDescending
        ];
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $output = $this->_field->getName();

        if ($this->_size !== null) {
            $output .= '(' . $this->_size . ')';
        }

        if ($this->_isDescending) {
            $output .= ' DESC';
        } else {
            $output .= ' ASC';
        }

        yield 'definition' => $output;
    }
}
