<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Structure implements core\debug\IDump {
    
    use core\TStringProvider;
    
    protected $_type;
    protected $_dumpId;
    protected $_properties;
    protected $_inspector;
    
    public function __construct(Inspector $inspector, $type, $dumpId, array $properties) {
        $this->_type = $type;
        $this->_dumpId = $dumpId;
        $this->_properties = array_values($properties);
        $this->_inspector = $inspector;
    }
    
    public function isArray() {
        return $this->_type === null;
    }
    
    public function toString() {
        $output = $this->_type;
        
        if($output === null) {
            $output = 'array';
        }
        
        $output .= '(';
        
        if(!empty($this->_properties)) {
            $output .= "\n".$this->_renderBody()."\n";
        }
        
        $output .= ')';
        
        return $output;
    }
    
    private function _renderBody() {
        $indent = '   ';
        $output = array();
        
        foreach($this->_properties as $property) {
            $dump = $property->inspectValue($this->_inspector);
            $line = $indent;
            
            if($property->hasName()) {
                $line .= '['.$property->getName().'] => ';
            }
            
            $line .= rtrim(str_replace("\n", "\n".$indent, $dump->toString()));
            $output[] = $line;
        }
        
        return implode(",\n", $output);
    }
}
