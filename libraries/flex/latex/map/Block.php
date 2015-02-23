<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\map;

use df;
use df\core;
use df\flex;
use df\iris;
    
class Block extends iris\map\Node implements flex\latex\IGenericBlock, core\IDumpable {

    use flex\latex\TContainerNode;
    use flex\latex\TReferable;
    use flex\latex\TListedNode;
    use core\collection\TAttributeContainer;

    protected $_isInline = false;
    protected $_type;

    public function isInline($flag=null) {
        if($flag !== null) {
            $this->_isInline = (bool)$flag;
            return $this;
        }

        return $this->_isInline;
    }


    public function setType($type) {
        $this->_type = $type;
        return $this;
    }

    public function getType() {
        return $this->_type;
    }

    public function containsOnlySpan() {
        if(count($this->_collection) > 1 || empty($this->_collection)) {
            return false;
        }

        if(!$this->_collection[0] instanceof self) {
            return false;
        }

        return in_array($this->_collection[0]->getType(), ['italic', 'align', 'bold']);
    }

// Dump
    public function getDumpProperties() {
        $output = [];

        if($this->id) {
            $output['id'] = new core\debug\dumper\Property('id', $this->id, 'protected');
        }

        $output['type'] = new core\debug\dumper\Property('type', $this->_type, 'protected');

        if(!empty($this->_attributes)) {
            foreach($this->_attributes as $key => $value) {
                $output[$key] = new core\debug\dumper\Property('@'.$key, $value, 'protected');
            }
        }

        $output = array_merge($output, $this->_collection);

        return $output;
    }
}