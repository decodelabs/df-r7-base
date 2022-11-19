<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use DecodeLabs\Exceptional;
use df\axis;
use df\mesh;

use df\opal;

class EntityLocator extends Base implements
    opal\schema\IMultiPrimitiveField,
    opal\schema\IQueryClauseRewriterField
{
    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (!isset($row[$key . '_domain'])) {
            return null;
        }

        $id = $row[$key . '_id'] ?? null;
        return mesh\entity\Locator::domainFactory($row[$key . '_domain'], $id);
    }

    public function deflateValue($value)
    {
        $value = $this->sanitizeValue($value);

        if (empty($value)) {
            return [
                $this->_name . '_domain' => null,
                $this->_name . '_id' => null
            ];
        }

        return [
            $this->_name . '_domain' => $value->getDomain(),
            $this->_name . '_id' => $value->getId()
        ];
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if (empty($value)) {
            if ($this->isNullable()) {
                return null;
            } elseif (!empty($this->_defaultValue)) {
                $value = $this->_defaultValue;
            } else {
                throw Exceptional::UnexpectedValue(
                    'This field cannot be null'
                );
            }
        }

        return mesh\entity\Locator::factory($value);
    }

    public function compareValues($value1, $value2)
    {
        return (string)$value1 === (string)$value2;
    }

    public function getSearchFieldType()
    {
        return 'string';
    }



    // Rewriters
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr = false)
    {
        switch ($operator) {
            case 'between':
            case 'not between':
                throw Exceptional::Logic(
                    'EntityLocator fields cannot be filtered with "' . $operator . '" operators'
                );

            case 'in':
                $subOperator = '=';
                break;

            case 'not in':
                $subOperator = '!=';
                break;

            default:
                $subOperator = $operator;
                break;
        }

        if (is_array($value)) {
            $output = new opal\query\clause\WhereList($parent, $isOr);

            foreach ($value as $sub) {
                $output->_addClause($this->_createSubClause($output, $field, $sub, $subOperator, true));
            }

            return $output;
        } else {
            return $this->_createSubClause($parent, $field, $value, $subOperator, $isOr);
        }
    }

    protected function _createSubClause(opal\query\IClauseFactory $parent, opal\query\IField $field, $value, $operator, $isOr = false)
    {
        $output = new opal\query\clause\WhereList($parent, $isOr);
        $sourceAlias = $field->getSource()->getAlias();

        if ($value instanceof opal\query\IField) {
            $output->whereField($sourceAlias . '.' . $this->_name . '_id', '=', $value->getName());
            return $output;
        }

        $locator = $this->sanitizeValue($value);

        if ($locator === null) {
            return $output
                ->where($sourceAlias . '.' . $this->_name . '_id', '=', $locator)
                ->where($sourceAlias . '.' . $this->_name . '_domain', '=', $locator);
        }

        switch ($operator) {
            case 'begins':
            case 'not begins':
                return $output->where($sourceAlias . '.' . $this->_name . '_domain', $operator, $locator->getDomain());


            default:
                if (opal\query\clause\Clause::isNegatedOperator($operator)) {
                    $idOperator = '!=';
                } else {
                    $idOperator = '=';
                }

                return $output
                    ->where($sourceAlias . '.' . $this->_name . '_domain', $operator, $locator->getDomain())
                    ->where($sourceAlias . '.' . $this->_name . '_id', $idOperator, $locator->getId());
        }
    }


    // Primitive
    public function getPrimitiveFieldNames()
    {
        return [
            $this->_name . '_domain',
            $this->_name . '_id'
        ];
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return new opal\schema\Primitive_MultiField($this, [
            $this->_name . '_domain' => (new opal\schema\Primitive_Varchar($this, 255)),
            $this->_name . '_id' => (new opal\schema\Primitive_Varchar($this, 64))
        ]);
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
    }

    public function toStorageArray()
    {
        return $this->_getBaseStorageArray();
    }
}
