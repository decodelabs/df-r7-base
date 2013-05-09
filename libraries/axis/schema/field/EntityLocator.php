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
    
class EntityLocator extends Base implements
    axis\schema\IMultiPrimitiveField,
    axis\schema\IQueryClauseRewriterField {

// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        if(!isset($row[$key.'_domain'])) {
            return null;
        }

        $id = isset($row[$key.'_id']) ? $row[$key.'_id'] : null;
        return core\policy\EntityLocator::domainFactory($row[$key.'_domain'], $id);
    }

    public function deflateValue($value) {
        $value = $this->sanitizeValue($value, true);

        if(empty($value)) {
            return null;
        }

        return [
            $this->_name.'_domain' => $value->getDomain(),
            $this->_name.'_id' => $value->getId()
        ];
    }

    public function sanitizeValue($value, $forRecord) {
        if(empty($value)) {
            if($this->isNullable()) {
                return null;
            } else if(!empty($this->_defaultValue)) {
                $value = $this->_defaultValue;
            } else {
                throw new axis\schema\UnexpectedValueException(
                    'This field cannot be null'
                );
            }
        }

        return core\policy\EntityLocator::factory($value);
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
                    'EntityLocator fields cannot be filtered with "'.$operator.'" operators'
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
        $locator = $this->sanitizeValue($value, false);

        if($locator === null) {
            return $output
                ->where($this->_name.'_id', '=', $locator)
                ->where($this->_name.'_domain', '=', $locator);
        }

        switch($operator) {
            case 'begins':
            case 'not begins':
                return $output->where($this->_name.'_domain', $operator, $locator->getDomain());


            default:
                if(opal\query\clause\Clause::isNegatedOperator($operator)) {
                    $idOperator = '!=';
                } else {
                    $idOperator = '=';
                }

                return $output
                    ->where($this->_name.'_domain', $operator, $locator->getDomain())
                    ->where($this->_name.'_id', $idOperator, $locator->getId());
        }
    }


// Primitive
    public function getPrimitiveFieldNames() {
        return [
            $this->_name.'_domain',
            $this->_name.'_id'
        ];
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_MultiField($this, [
            $this->_name.'_domain' => (new opal\schema\Primitive_Varchar($this, 255)),
            $this->_name.'_id' => (new opal\schema\Primitive_Varchar($this, 64))
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