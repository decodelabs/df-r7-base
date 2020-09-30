<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\schema;

use df;
use df\core;
use df\opal;

use DecodeLabs\Exceptional;

class ForeignKeyFieldReference implements IForeignKeyFieldReference
{
    protected $_field;
    protected $_targetFieldName;

    public function __construct(IField $field, $targetFieldName)
    {
        $this->_setField($field);
        $this->_setTargetFieldName($targetFieldName);
    }

    public function _setField(IField $field)
    {
        $this->_field = $field;
        return $this;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function _setTargetFieldName($targetFieldName)
    {
        $this->_targetFieldName = $targetFieldName;
        return $this;
    }

    public function getTargetFieldName()
    {
        return $this->_targetFieldName;
    }

    public function eq(IForeignKeyFieldReference $reference)
    {
        return $this->_field === $reference->getField()
            && $this->_targetFieldName == $reference->getTargetFieldName();
    }


    public static function fromStorageArray(opal\schema\ISchema $schema, array $data)
    {
        if (!$schema instanceof opal\schema\IFieldProvider) {
            throw Exceptional::Runtime(
                'Schem does not provider fields', null, $schema
            );
        }

        return new self($schema->getField($data[0]), $data[1]);
    }

    public function toStorageArray()
    {
        return [
            $this->_field->getName(),
            $this->_targetFieldName
        ];
    }
}
