<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Structure implements IStructureNode {

    use core\TStringProvider;
    use TNode;

    protected $_type;
    protected $_dumpId;
    protected $_properties;

    public function __construct(Inspector $inspector, $type, $dumpId, array $properties) {
        $this->_inspector = $inspector;
        $this->_type = $type;
        $this->_dumpId = $dumpId;
        $this->_properties = array_values($properties);
    }

    public function isArray() {
        return $this->_type === null;
    }

    public function getType() {
        return $this->_type;
    }

    public function getDumpId() {
        return $this->_dumpId;
    }

    public function getProperties() {
        return $this->_properties;
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

    public function getDataValue() {
        $output = [];

        if($this->_type) {
            $output['___class_name'] = $this->_type;
        }

        foreach($this->_properties as $property) {
            $name = $property->getName();

            if($property->isPrivate()) {
                $name = '§ '.$name;
            } else if($property->isProtected()) {
                $name = '± '.$name;
            }

            $value = $property->getValue();
            $output[$name] = $this->_inspector->inspect($value)->getDataValue();
            unset($value);
        }

        return $output;
    }

    private function _renderBody() {
        $indent = '   ';
        $output = [];

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
