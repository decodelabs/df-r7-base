<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\table;

use df;
use df\core;
use df\neon;

class Style implements neon\vector\dxf\IStyleTable {
    
    use neon\vector\dxf\TTable;
    use neon\vector\dxf\TTextProvider;
    
    protected $_lastHeightUsed = 1;
    protected $_primaryFontFileName = 'ARIAL.TTF';
    protected $_bigFontFileName;

    public function getType() {
        return 'STYLE';
    }

    

    public function setLastHeightUsed($height) {
        if($height !== null) {
            $height = (float)$height;
        }

        $this->_lastHeightUsed = $height;
        return $this;
    }

    public function getLastHeightUsed() {
        return $this->_lastHeightUsed;
    }

    public function setPrimaryFontFileName($fileName) {
        $this->_primaryFontFileName = $fileName;
        return $this;
    }

    public function getPrimaryFontFileName() {
        return $this->_primaryFontFileName;
    }

    public function setBigFontFileName($fileName) {
        $this->_bigFontFileName = $fileName;
        return $this;
    }

    public function getBigFontFileName() {
        return $this->_bigFontFileName;
    }

    public function toString() {
        $output = sprintf(" 40\n%F\n", $this->_height ? $this->_height : 0);
        $output .= sprintf(" 41\n%F\n", $this->_widthFactor);
        $output .= sprintf(" 50\n%F\n", $this->_obliqueAngle);

        $textFlags = 0;

        if($this->_mirrorX) {
            $textFlags &= 2;
        }

        if($this->_mirrorY) {
            $textFlags &= 4;
        }

        $output .= sprintf(" 71\n%u\n", $textFlags);
        $output .= sprintf(" 42\n%F\n", $this->_lastHeightUsed);
        $output .= sprintf(" 3\n%s\n", $this->_primaryFontFileName);

        if($this->_bigFontFileName !== null) {
            $output .= sprintf(" 4\n%s\n", $this->_bigFontFileName);
        }

        return $this->_writeBaseString($output);
    }
}