<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;
    
class PathSlug extends Base implements 
    axis\schema\IAutoUniqueField, 
    axis\schema\IMultiPrimitiveField,
    axis\schema\IQueryClauseRewriterField {


// Values
    public function inflateValueFromRow($key, array $row, opal\query\record\IRecord $forRecord=null) {
        if(!isset($row[$key.'_name'])) {
            return null;
        }

        $output = $row[$key.'_name'];
        $location = trim($row[$key.'_location'], '/');

        if(!empty($location)) {
            $output = $location.'/'.$output;
        }

        return $output;
    }

    public function deflateValue($value) {
        $parts = explode('/', $value);
        $name = array_pop($parts);
        $location = implode('/', $parts);

        return [
            $this->_name.'_name' => $name,
            $this->_name.'_location' => $location
        ];
    }

    public function sanitizeValue($value, $forRecord) {
        if($value === null && $this->isNullable()) {
            return null;
        }

        return core\string\Manipulator::formatPathSlug($value);
    }

    public function compareValues($value1, $value2) {
        return (string)$value1 === (string)$value2;
    }
    

// Rewriters
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        switch($operator) {
            case 'between':
            case 'not between':
                throw new axis\LogicException(
                    'PathSlug fields cannot be filtered with "'.$operator.'" operators'
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

        if(is_array($value)) {
            $output = new opal\query\clause\WhereList($parent, $isOr);

            foreach($value as $sub) {
                $output->_addClause($this->_createSubClause($output, $sub, $subOperator));
            }

            return $output;
        } else {
            return $this->_createSubClause($parent, $value, $subOperator);
        }
    }

    protected function _createSubClause(opal\query\IClauseFactory $parent, $value, $operator) {
        $output = new opal\query\clause\WhereList($parent, true);
        $slug = $this->sanitizeValue($value, false);

        if($slug === null) {
            return $output
                ->where($this->_name.'_name', '=', $slug)
                ->where($this->_name.'_location', '=', $slug);
        }

        switch($operator) {
            case 'begins':
            case 'not begins':
                return $output->where($this->_name.'_location', $operator, $slug);


            default:
                $parts = explode('/', $slug);
                $name = array_pop($parts);
                $location = '';

                if(opal\query\clause\Clause::isNegatedOperator($operator)) {
                    $nameOperator = '!=';
                } else {
                    $nameOperator = '=';
                }

                if(!empty($parts)) {
                    $location = implode('/', $parts);
                }

                return $output
                    ->where($this->_name.'_name', $nameOperator, $name)
                    ->where($this->_name.'_location', $operator, $location);
        }
    }


// Primitive
    public function getPrimitiveFieldNames() {
        return [
            $this->_name.'_name',
            $this->_name.'_location'
        ];
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_MultiField($this, [
            $this->_name.'_name' => (new opal\schema\Primitive_Varchar($this, 255)),
            $this->_name.'_location' => (new opal\schema\Primitive_Varchar($this, 255))
        ]);
    }

// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
    }

    public function toStorageArray() {
        return $this->_getBaseStorageArray();
    }
}