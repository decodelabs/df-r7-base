<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\schema;

use df;
use df\core;
use df\opal;

class IndexFieldReference implements IIndexFieldReference, core\IDumpable {

    protected $_field;
    protected $_size;
    protected $_isDescending = false;

    public function __construct(opal\schema\IField $field, $size=null, $isDescending=false) {
        if(is_string($isDescending)) {
            $isDescending = strtoupper($isDescending) == 'DESC';
        }

        if($isDescending === null) {
            $isDescending = false;
        }

        $this->_setField($field);
        $this->setSize($size);
        $this->isDescending((bool)$isDescending);
    }

    public function _setField(opal\schema\IField $field) {
        $this->_field = $field;
        return $this;
    }

    public function getField() {
        return $this->_field;
    }

    public function isMultiField() {
        return $this->_field instanceof opal\schema\IMultiPrimitiveField;
    }

    public function setSize($size) {
        if($size !== null) {
            $size = (int)$size;
        }

        if($size == 0) {
            $size = null;
        }

        $this->_size = $size;
        return $this;
    }

    public function getSize() {
        return $this->_size;
    }

    public function isDescending(bool $flag=null) {
        if($flag !== null) {
            $this->_isDescending = $flag;
            return $this;
        }

        return $this->_isDescending;
    }

    public static function fromStorageArray(opal\schema\ISchema $schema, array $data) {
        return new self($schema->getField($data['fld']), $data['siz'], $data['des']);
    }

    public function toStorageArray() {
        return [
            'fld' => $this->_field->getName(),
            'siz' => $this->_size,
            'des' => $this->_isDescending
        ];
    }


// Dump
    public function getDumpProperties() {
        $output = $this->_field->getName();

        if($this->_size !== null) {
            $output .= '('.$this->_size.')';
        }

        if($this->_isDescending) {
            $output .= ' DESC';
        } else {
            $output .= ' ASC';
        }

        return $output;
    }
}
