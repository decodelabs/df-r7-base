<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\table;

use df;
use df\core;
use df\neon;

class Layer implements neon\vector\dxf\ILayerTable {
    
    use neon\vector\dxf\TTable;

    protected $_color = 7;
    protected $_lineType = 'CONTINUOUS';

    public function getType() {
        return 'LAYER';
    }

    public function setColor($color) {
        if($color !== null) {
            $color = (int)$color;
        }

        $this->_color = $color;
        return $this;
    }

    public function getColor() {
        return $this->_color;
    }

    public function setLineType($name) {
        if($name !== null) {
            $name = (string)$name;
        }

        $this->_lineType = $name;
        return $this;
    }

    public function getLineType() {
        return $this->_lineType;
    }


    public function toString() {
        $output = '';

        if($this->_color !== null) {
            $output .= sprintf(" 62\n%u\n", $this->_color);
        }

        if($this->_lineType !== null) {
            $output .= sprintf(" 6\n%s\n", $this->_lineType);
        }

        return $this->_writeBaseString($output);
    }
}