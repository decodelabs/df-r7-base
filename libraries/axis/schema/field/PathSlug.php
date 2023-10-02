<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\field;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;

use df\axis;
use df\opal;

class PathSlug extends Base implements
    axis\schema\IAutoUniqueField,
    opal\schema\IMultiPrimitiveField,
    opal\schema\IQueryClauseRewriterField
{
    use axis\schema\TAutoUniqueField;

    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (!isset($row[$key . '_name'])) {
            return null;
        }

        $output = $row[$key . '_name'];

        if (isset($row[$key . '_location'])) {
            $location = trim((string)$row[$key . '_location'], '/');
        } else {
            $location = null;
        }

        if (!empty($location)) {
            $output = $location . '/' . $output;
        }

        return $output;
    }

    public function deflateValue($value)
    {
        $parts = explode('/', $value);
        $name = array_pop($parts);
        $location = implode('/', $parts);

        if ($location == '/') {
            $location = '';
        }

        return [
            $this->_name . '_location' => $location,
            $this->_name . '_name' => $name
        ];
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if ($value === null && $this->isNullable()) {
            return null;
        }

        $output = Dictum::pathSlug($value);

        if ($output == '/') {
            $output = '';
        }

        return $output;
    }

    public function compareValues($value1, $value2)
    {
        return (string)$value1 === (string)$value2;
    }


    // Rewriters
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr = false)
    {
        switch ($operator) {
            case 'between':
            case 'not between':
                throw Exceptional::Logic(
                    'PathSlug fields cannot be filtered with "' . $operator . '" operators'
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
                $output->_addClause($this->_createSubClause($output, $field, $sub, $subOperator));
            }

            return $output;
        } else {
            return $this->_createSubClause($parent, $field, $value, $subOperator);
        }
    }

    protected function _createSubClause(opal\query\IClauseFactory $parent, opal\query\IField $field, $value, $operator)
    {
        $output = new opal\query\clause\WhereList($parent, true);
        $slug = $this->sanitizeValue($value);
        $sourceAlias = $field->getSource()->getAlias();

        if ($slug === null) {
            return $output
                ->where($sourceAlias . '.' . $this->_name . '_name', '=', $slug)
                ->where($sourceAlias . '.' . $this->_name . '_location', '=', $slug);
        }

        switch ($operator) {
            case 'begins':
            case 'not begins':
                return $output->where($sourceAlias . '.' . $this->_name . '_location', $operator, $slug);

            case 'ends':
            case 'not ends':
                return $output->where($sourceAlias . '.' . $this->_name . '_name', $operator, $slug);

            case 'contains':
            case 'not contains':
            case 'matches':
            case 'not matches':
                return $output
                    ->where($sourceAlias . '.' . $this->_name . '_name', $operator, $slug)
                    ->orWhere($sourceAlias . '.' . $this->_name . '_location', $operator, $slug);

            default:
                $parts = explode('/', $slug);
                $name = array_pop($parts);
                $location = '';

                if (opal\query\clause\Clause::isNegatedOperator($operator)) {
                    $nameOperator = '!=';
                } else {
                    $nameOperator = '=';
                }

                if (!empty($parts)) {
                    $location = implode('/', $parts);
                }

                if ($location == '/') {
                    $location = '';
                }

                return $output
                    ->where($sourceAlias . '.' . $this->_name . '_location', $operator, $location)
                    ->where($sourceAlias . '.' . $this->_name . '_name', $nameOperator, $name);
        }
    }


    // Primitive
    public function getPrimitiveFieldNames()
    {
        return [
            $this->_name . '_location',
            $this->_name . '_name'
        ];
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return new opal\schema\Primitive_MultiField($this, [
            $this->_name . '_location' => (new opal\schema\Primitive_Varchar($this, 255)),
            $this->_name . '_name' => (new opal\schema\Primitive_Varchar($this, 255))
        ]);
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_setAutoUniqueStorageArray($data);
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getAutoUniqueStorageArray()
        );
    }
}
