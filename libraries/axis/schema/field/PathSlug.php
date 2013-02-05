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

        if(isset($row[$key.'_location']) && !empty($row[$key.'_location'])) {
            $output = $row[$key.'_location'].'/'.$output;
        }

        return $output;
    }

    public function deflateValue($value) {
        $parts = explode('/', $value);
        $name = array_pop($parts);
        $location = implode('/', $parts);

        if(empty($location)) {
            $location = null;
        }

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
            core\dump($operator);
        if(is_array($value)) {
            $output = new opal\query\clause\WhereList($parent, $isOr);


            switch($operator) {
            }

            foreach($value as $sub) {

            }
        }
    }

    protected function _createSubClause(opal\query\IClauseFactory $parent, $slug, $operator) {
        $output = new opal\query\clause\WhereList($parent, true);
        $slug = $this->sanitizeValue($slug, false);
        $name = null;
        $location = null;

        if($slug !== null) {
            $parts = explode('/', $slug);
            $name = array_pop($parts);

            if(!empty($parts)) {
                $location = implode('/', $parts);
            }
        }

        $output->where($this->_name.'_name', $operator, $name)
            ->where($this->_name.'_location', $operator, $location);

        return $output;
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
                ->isNullable(true)
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